<?php
namespace App\Services\GciReconciliation;

use App\Curation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Workflow
{
    public function upload(Run $run, UploadedFile $file, string $source, int $actor): Upload
    {
        $payload = (new PayloadReader())->read($file->get(), $source);
        $hash = hash_file('sha256', $file->getRealPath());
        return DB::transaction(function () use ($run, $file, $source, $actor, $payload, $hash) {
            $run = Run::lockForUpdate()->findOrFail($run->uuid);
            if ($run->status !== 'DRAFT') $this->invalid('Uploads are only allowed on draft runs.');
            $existing = $run->uploads()->where('source_type', $source)->first();
            if ($existing) {
                if ($existing->sha256 === $hash) return $existing;
                $this->invalid('This source type is already uploaded. Create a new run for a different file.');
            }
            $directory = storage_path('app/gci-reconciliation/'.$run->uuid.'/inputs');
            $name = Str::uuid().'.json';
            $file->move($directory, $name);
            try {
                return Upload::create(['run_uuid' => $run->uuid, 'source_type' => $source,
                    'filename' => $file->getClientOriginalName(), 'path' => $directory.'/'.$name,
                    'sha256' => $hash, 'record_count' => count($payload['Items']), 'uploaded_by' => $actor,
                    'metadata' => array_intersect_key($payload, array_flip(['ScannedCount', 'ConsumedCapacity']))]);
            } catch (\Throwable $e) { @unlink($directory.'/'.$name); throw $e; }
        });
    }

    public function reconcile(Run $run, int $actor): Run
    {
        DB::transaction(function () use ($run, $actor) {
            $locked = Run::lockForUpdate()->findOrFail($run->uuid);
            if (!in_array($locked->status, ['DRAFT', 'FAILED'], true)) $this->invalid('This run has already started.');
            if ($locked->uploads()->count() !== 2) $this->invalid('Upload both source types before reconciliation.');
            $locked->update(['status' => 'RUNNING', 'started_by' => $actor, 'started_at' => now(), 'error' => null]);
        });
        try {
            DB::transaction(function () use ($run) {
                $files = $run->uploads()->get()->keyBy('source_type');
                foreach ($files as $file) {
                    if (!is_file($file->path) || hash_file('sha256', $file->path) !== $file->sha256) $this->invalid('Stored upload hash does not match.');
                }
                app(Reconciler::class)->reconcile($run->uuid, $run->label, $files[PayloadReader::HAS]->path, $files[PayloadReader::NO]->path);
                foreach ($files as $file) Record::where('run_uuid', $run->uuid)->where('source_type', $file->source_type)->update(['source_filename' => $file->filename]);
                $run->update(['status' => 'COMPLETED', 'completed_at' => now()]);
            });
        } catch (\Throwable $e) {
            $run->update(['status' => 'FAILED', 'error' => $e->getMessage()]);
            throw $e;
        }
        return $run->fresh('uploads');
    }

    public function candidates(Record $row): array
    {
        if ($row->match_status !== 'NOT_FOUND_IN_GT') return [];
        return DB::table('curations as c')->join('expert_panels as ep', 'ep.id', '=', 'c.expert_panel_id')
            ->join('affiliations as a', 'a.id', '=', 'ep.affiliation_id')->join('affiliations as p', 'p.id', '=', 'a.parent_id')
            ->whereNull('c.deleted_at')->where('c.gene_symbol', $row->gci_gene_symbol)->where('p.clingen_id', $row->gci_affiliation_id)
            ->select(['c.id', 'c.gdm_uuid', 'c.mondo_id', 'c.moi_id', 'c.curation_status_id', 'c.archived_at'])->orderBy('c.id')->get()->toArray();
    }

    public function export(array $ids, int $actor): array
    {
        return DB::transaction(function () use ($ids, $actor) {
            $items = []; $seen = [];
            foreach (Record::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get() as $r) {
                if ($r->source_type !== PayloadReader::NO || in_array($r->execution_status, ['FAILED', 'NO_ACTION'], true)
                    || in_array($r->resolution, array_merge(Decision::NONE, ['NEEDS_FURTHER_REVIEW']), true)
                    || $r->review_status === 'NEEDS_FURTHER_REVIEW') continue;
                $direct = $r->match_status === 'CONFIRMED' && $r->match_method === 'GDM_UUID' && !$r->has_identity_warning
                    && $r->gene_match && $r->affiliation_match && !$r->resolution;
                $approved = $r->review_status === 'REVIEWED' && $r->execution_status === 'APPLIED'
                    && in_array($r->resolution, [Decision::MATCH, Decision::CREATE, Decision::EXPORT], true);
                if (!$direct && !$approved) continue;
                $c = Curation::find($approved ? $r->resolved_gt_curation_id : $r->gt_curation_id);
                if (!$c || $c->archived_at || $c->curation_status_id == config('curations.statuses.retired-assignment')) continue;
                if ($direct && (strtolower((string) $c->gdm_uuid) !== $r->gci_gdm_uuid
                    || strtoupper($c->gene_symbol) !== $r->gci_gene_symbol
                    || $c->expertPanel?->affiliation?->parent?->clingen_id != $r->gci_affiliation_id
                    || Curation::where('gdm_uuid', $r->gci_gdm_uuid)->count() !== 1)) continue;
                if (isset($seen[$r->gci_gdm_uuid])) $this->invalid('Selected rows contain duplicate GCI UUIDs. Export one run at a time.');
                $seen[$r->gci_gdm_uuid] = true;
                $items[] = array_merge(array_intersect_key($r->raw_payload, array_flip(['gene', 'modeInheritance', 'affiliation', 'disease', 'PK'])), ['precurationId' => (string) $c->id]);
                $r->update(['exported_at' => now(), 'exported_by' => $actor]);
                app(GciReconciliationExecutor::class)->audit($r, 'EXPORTED', $actor, [], ['precurationId' => (string) $c->id]);
            }
            if (!$items) $this->invalid('None of the selected records is eligible for export.');
            // Same envelope as the supplied GCI files; these counts describe this export, not the original scan.
            return ['Items' => $items, 'Count' => count($items), 'ScannedCount' => count($items), 'ConsumedCapacity' => null];
        });
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['workflow' => $message]);
    }
}
