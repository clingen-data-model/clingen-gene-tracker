<?php
namespace App\Services\GciReconciliation;

use App\{Curation, CurationStatus, Disease, Gene, ModeOfInheritance};
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** The sole GT mutation boundary; future unified-history integration belongs here. */
class GciReconciliationExecutor
{
    public function audit(Record $row, string $kind, int $actor, array $before = [], array $after = [], ?string $error = null): void
    {
        DB::table('gci_reconciliation_audits')->insert([
            'reconciliation_id' => $row->id, 'kind' => $kind, 'source' => 'gci_reconciliation',
            'source_id' => $row->gci_gdm_uuid, 'source_event_key' => 'gci-reconciliation:'.$row->run_uuid.':'.$row->id.':'.$kind,
            'actor_id' => $actor, 'action' => $row->resolution,
            'before_values' => json_encode($before), 'after_values' => json_encode($after), 'error' => $error,
            'effective_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function review(array $ids, array $decision, int $actor): void
    {
        DB::transaction(function () use ($ids, $decision, $actor) {
            foreach ($ids as $id) {
                $row = Record::lockForUpdate()->findOrFail($id);
                $this->require($row->execution_status !== 'APPLIED', 'Applied decisions cannot be changed.');
                $before = $row->only(['review_status', 'resolution', 'review_notes', 'resolved_gt_curation_id']);
                $row->fill($decision);
                $row->resolved_gt_curation_id = $decision['resolved_gt_curation_id'] ?? null;
                if ($row->review_status === 'REVIEWED') {
                    $this->validateDecision($row);
                    $row->execution_status = in_array($row->resolution, Decision::NONE, true) ? 'NO_ACTION' : 'PENDING';
                } else {
                    $row->execution_status = 'NOT_READY';
                }
                $row->reviewed_by = $actor;
                $row->reviewed_at = now();
                $row->execution_error = null;
                $row->save();
                $this->audit($row, 'REVIEW', $actor, $before, $row->only(array_keys($before)));
            }
        });
    }

    private function validateDecision(Record $r): void
    {
        $this->require(in_array($r->resolution, Decision::RESOLUTIONS, true) && $r->resolution !== 'NEEDS_FURTHER_REVIEW', 'Choose a final resolution.');
        if (in_array($r->resolution, Decision::NONE, true)) return;
        $this->require(!$r->gt_is_retired && !$r->gt_archived_at, 'Retired or archived records require a no-action decision.');
        if ($r->resolution === Decision::UPDATE) {
            $this->require($r->source_type === PayloadReader::HAS && $r->match_method === 'PRECURATION_ID'
                && $r->precuration_id_match && $r->gdm_uuid_match && $r->gene_match && $r->affiliation_match
                && !$r->has_identity_warning && $r->safe_update_candidate, 'This record is not a safe disease/MoI update.');
        }
        if ($r->resolution === Decision::UUID) {
            $this->require($r->source_type === PayloadReader::HAS && $r->match_method === 'PRECURATION_ID'
                && $r->precuration_id_match && $r->gt_curation_id && !$r->gdm_uuid_match, 'UUID correction requires a precuration-ID match with a UUID mismatch.');
        }
        if ($r->resolution === Decision::CREATE) {
            $this->require($r->source_type === PayloadReader::NO && $r->match_status === 'NOT_FOUND_IN_GT'
                && !$r->gt_curation_id && !$r->resolved_gt_curation_id, 'Creation requires an unmatched record without a precuration ID.');
        }
        if (in_array($r->resolution, [Decision::MATCH, Decision::EXPORT], true)) {
            $this->require($r->source_type === PayloadReader::NO, 'This resolution requires a record without a precuration ID.');
            $this->require((bool) ($r->resolved_gt_curation_id ?: $r->gt_curation_id), 'Select a resolved GT curation.');
            if ($r->resolution === Decision::MATCH) $this->require((bool) $r->resolved_gt_curation_id, 'Select a resolved GT curation.');
            $this->target($r);
        }
        if (in_array($r->resolution, [Decision::UPDATE, Decision::UUID], true)) {
            $this->require(!$r->resolved_gt_curation_id || $r->resolved_gt_curation_id == $r->gt_curation_id, 'An update cannot redirect the automated match.');
        }
    }

    private function target(Record $r): Curation
    {
        $c = Curation::find($r->resolved_gt_curation_id ?: $r->gt_curation_id);
        $this->require($c !== null, 'The GT curation is missing or deleted.');
        $this->require(!$c->archived_at && $c->curation_status_id != config('curations.statuses.retired-assignment'), 'The GT curation is retired or archived.');
        return $c;
    }

    private function mappings(Record $r): array
    {
        $disease = Disease::where('mondo_id', $r->gci_mondo_id)->where('is_obsolete', false)->get();
        $moi = ModeOfInheritance::where('hp_id', $r->gci_moi_hp_id)->get();
        $this->require($r->gci_mondo_id && $disease->count() === 1, 'Disease cannot be resolved to one non-obsolete MONDO term.');
        $this->require($r->gci_moi_hp_id && $moi->count() === 1, 'MoI cannot be resolved to one HP term.');
        return ['mondo_id' => $disease->first()->mondo_id, 'moi_id' => $moi->first()->id];
    }

    public function plan(Record $r): array
    {
        $this->require($r->review_status === 'REVIEWED' && in_array($r->execution_status, ['PENDING', 'FAILED'], true), 'Record is not approved for execution or is already applied.');
        $this->validateDecision($r);
        $values = [];
        $c = null;
        if ($r->resolution === Decision::CREATE) {
            $this->require(!Curation::withTrashed()->where('gdm_uuid', $r->gci_gdm_uuid)->exists(), 'A GT curation already uses this GDM UUID (possibly deleted).');
            $genes = Gene::where('gene_symbol', $r->gci_gene_symbol)->get();
            $this->require($genes->count() === 1 && $genes->first()->hgnc_id && $genes->first()->hgnc_name, 'Gene cannot be resolved uniquely.');
            $panels = DB::table('expert_panels as ep')->join('affiliations as a', 'a.id', '=', 'ep.affiliation_id')
                ->join('affiliations as p', 'p.id', '=', 'a.parent_id')->where('p.clingen_id', $r->gci_affiliation_id)
                ->select('ep.id', 'p.id as parent_affiliation_id')->get();
            $this->require($panels->count() === 1, 'Affiliation must resolve to exactly one active expert panel.');
            $status = CurationStatus::find(config('curations.statuses.uploaded'));
            $this->require($status && strcasecmp($status->name, 'Uploaded') === 0, 'Configured Uploaded status is unavailable.');
            $values = array_merge($this->mappings($r), [
                'gene_symbol' => $genes->first()->gene_symbol, 'hgnc_id' => $genes->first()->hgnc_id,
                'hgnc_name' => $genes->first()->hgnc_name, 'expert_panel_id' => $panels->first()->id,
                'affiliation_id' => $panels->first()->parent_affiliation_id,
                'gdm_uuid' => $r->gci_gdm_uuid, 'curation_status_id' => $status->id,
            ]);
        } else {
            $c = $this->target($r);
            if ($r->resolution === Decision::UPDATE) {
                $parent = $c->expertPanel?->affiliation?->parent;
                $this->require(strtolower((string) $c->gdm_uuid) === $r->gci_gdm_uuid
                    && strtoupper($c->gene_symbol) === $r->gci_gene_symbol
                    && $parent && $parent->clingen_id == $r->gci_affiliation_id, 'GT identity has changed since reconciliation.');
                $values = $this->mappings($r);
            } elseif ($r->resolution === Decision::UUID) {
                $this->require(strtolower((string) $c->gdm_uuid) === (string) $r->gt_gdm_uuid, 'GT UUID has changed since reconciliation.');
                $this->require(!Curation::withTrashed()->where('gdm_uuid', $r->gci_gdm_uuid)->where('id', '!=', $c->id)->exists(), 'The proposed UUID already belongs to another GT record.');
                $values = ['gdm_uuid' => $r->gci_gdm_uuid];
            }
        }
        $before = $c ? $c->only(['id', 'gdm_uuid', 'gene_symbol', 'expert_panel_id', 'affiliation_id', 'mondo_id', 'moi_id', 'curation_status_id', 'archived_at', 'updated_at']) : [];
        $changes = [];
        foreach ($values as $key => $value) {
            if (!$c || (string) $c->$key !== (string) $value) $changes[$key] = ['from' => $c?->$key, 'to' => $value];
        }
        return ['id' => $r->id, 'resolution' => $r->resolution, 'target_id' => $c?->id,
            'reviewed_at' => $r->reviewed_at?->toISOString(), 'reviewed_by' => $r->reviewed_by,
            'before' => $before, 'values' => $values, 'changes' => $changes];
    }

    public function preview(array $ids, int $actor): array
    {
        $plans = $errors = [];
        foreach ($ids as $id) {
            try { $plans[] = $this->plan(Record::findOrFail($id)); }
            catch (\Throwable $e) { $errors[] = ['id' => $id, 'error' => $e->getMessage()]; }
        }
        $uuid = (string) Str::uuid();
        DB::table('gci_reconciliation_previews')->insert(['uuid' => $uuid, 'actor_id' => $actor,
            'plans' => json_encode($plans), 'expires_at' => now()->addMinutes(15), 'created_at' => now(), 'updated_at' => now()]);
        return ['uuid' => $uuid, 'plans' => $plans, 'errors' => $errors, 'expires_in_minutes' => 15,
            'summary' => ['selected' => count($ids), 'ready' => count($plans),
                'disease_updates' => count(array_filter($plans, fn ($p) => $p['target_id'] && isset($p['changes']['mondo_id']))),
                'moi_updates' => count(array_filter($plans, fn ($p) => $p['target_id'] && isset($p['changes']['moi_id']))),
                'uuid_updates' => count(array_filter($plans, fn ($p) => $p['target_id'] && isset($p['changes']['gdm_uuid']))),
                'new_curations' => count(array_filter($plans, fn ($p) => $p['resolution'] === Decision::CREATE))]];
    }

    public function execute(string $uuid, int $actor): array
    {
        // Consume once, before processing. Each record commits independently.
        $plans = DB::transaction(function () use ($uuid, $actor) {
            $preview = DB::table('gci_reconciliation_previews')->where('uuid', $uuid)->lockForUpdate()->first();
            $this->require($preview && $preview->actor_id == $actor && !$preview->consumed_at && now()->lt($preview->expires_at), 'Preview is expired, consumed, or belongs to another user.');
            DB::table('gci_reconciliation_previews')->where('uuid', $uuid)->update(['consumed_at' => now()]);
            return json_decode($preview->plans, true);
        });
        $results = [];
        foreach ($plans as $expected) {
            try {
                DB::transaction(function () use ($expected, $actor) {
                    $r = Record::lockForUpdate()->findOrFail($expected['id']);
                    // Serialize reconciliation writes across runs, including UUID creation checks.
                    DB::table('gci_reconciliation_runs')->orderBy('uuid')->lockForUpdate()->get();
                    Curation::withTrashed()->where('gdm_uuid', $r->gci_gdm_uuid)->lockForUpdate()->get();
                    if ($expected['target_id']) Curation::whereKey($expected['target_id'])->lockForUpdate()->first();
                    $plan = $this->plan($r);
                    $this->require($this->canonical($plan) === $this->canonical($expected), 'GT data or the review decision changed; preview again.');
                    $c = $plan['target_id'] ? Curation::findOrFail($plan['target_id']) : null;
                    if ($r->resolution === Decision::CREATE) {
                        // Existing bulk-upload flag avoids default asynchronous status/owner hooks.
                        $previous = config('app.bulk_uploading');
                        config(['app.bulk_uploading' => true]);
                        try {
                            $c = Curation::create($plan['values']);
                            $c->expertPanels()->attach($c->expert_panel_id, ['start_date' => now(), 'end_date' => null]);
                            AddStatus::dispatchSync($c, CurationStatus::findOrFail($plan['values']['curation_status_id']), now());
                        } finally { config(['app.bulk_uploading' => $previous]); }
                    } elseif ($plan['values']) {
                        $c->fill($plan['values']);
                        $c->save();
                        // Model listeners must never silently alter identity during synchronization.
                        $this->require($c->gene_symbol === $plan['before']['gene_symbol'] && $c->expert_panel_id == $plan['before']['expert_panel_id'], 'A model listener attempted to change gene or expert panel.');
                    }
                    $r->fill(['resolved_gt_curation_id' => $c->id, 'execution_status' => 'APPLIED', 'executed_by' => $actor,
                        'executed_at' => now(), 'execution_error' => null])->save();
                    $this->audit($r, 'APPLIED', $actor, $plan['before'], $c->fresh()->only(['id', 'gene_symbol', 'expert_panel_id', 'affiliation_id', 'gdm_uuid', 'mondo_id', 'moi_id', 'curation_status_id']));
                });
                $results[] = ['id' => $expected['id'], 'status' => 'APPLIED'];
            } catch (\Throwable $e) {
                DB::transaction(function () use ($expected, $actor, $e) {
                    $r = Record::lockForUpdate()->findOrFail($expected['id']);
                    if ($r->execution_status === 'APPLIED') return;
                    $r->fill(['execution_status' => 'FAILED', 'execution_error' => $e->getMessage(), 'executed_by' => $actor, 'executed_at' => now()])->save();
                    $this->audit($r, 'FAILED', $actor, $expected['before'], [], $e->getMessage());
                });
                $results[] = ['id' => $expected['id'], 'status' => 'FAILED', 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    private function canonical(array $value): string
    {
        $value = json_decode(json_encode($value), true);
        $sort = function (array &$items) use (&$sort) {
            foreach ($items as &$item) if (is_array($item)) $sort($item);
            if (!array_is_list($items)) ksort($items);
        };
        $sort($value);
        return json_encode($value);
    }

    private function require(bool $condition, string $message): void
    {
        if (!$condition) throw ValidationException::withMessages(['decision' => $message]);
    }
}
