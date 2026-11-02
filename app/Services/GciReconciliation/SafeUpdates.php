<?php
namespace App\Services\GciReconciliation;

use App\{Curation, Disease, ModeOfInheritance};
use App\Services\Curations\UpdateCuration;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SafeUpdates
{
    public function plan(Record $r): array
    {
        $this->check(Record::whereKey($r->id)->safeIdentity()->exists(), 'Snapshot identity is not a clean confirmed match.');
        $this->check(!$r->gt_is_retired && !$r->gt_archived_at && !$r->gt_deleted_at
            && empty($r->conflicting_gt_curation_ids) && empty($r->deleted_gt_curation_ids), 'Historical, deleted or conflicting snapshot.');
        $this->check(!array_diff($r->issue_codes ?? [], ['DISEASE_MISMATCH', 'MOI_MISMATCH', 'GCI_FREETEXT_DISEASE']), 'Unresolved identity issue.');
        $this->check($r->review_status !== 'NEEDS_FURTHER_REVIEW'
            && in_array($r->resolution, [null, Decision::UPDATE, Decision::EXPORT], true), 'Review decision excludes automatic disease/MoI synchronization.');
        $this->check(!$r->resolved_gt_curation_id || $r->resolved_gt_curation_id == $r->gt_curation_id, 'Manual target differs from the automated match.');
        $this->check($r->execution_status !== 'APPLIED', 'Already applied; further corrections require manual review.');
        $this->check($r->disease_match === 0 || $r->disease_match === false || $r->moi_match === 0 || $r->moi_match === false,
            'No disease/MoI discrepancy in the original snapshot.');
        $c = Curation::with(['expertPanel.affiliation.parent', 'modeOfInheritance'])->find($r->gt_curation_id);
        $this->check($c && !$c->archived_at && $c->curation_status_id != config('curations.statuses.retired-assignment'), 'Live GT curation is missing, deleted, archived or retired.');
        $this->check($r->source_type !== PayloadReader::HAS || $c->id == $r->gci_precuration_id, 'Live precuration ID mismatch.');
        $this->check(strtolower((string) $c->gdm_uuid) === $r->gci_gdm_uuid
            && strtoupper($c->gene_symbol) === $r->gci_gene_symbol
            && $c->expertPanel?->affiliation?->parent?->clingen_id == $r->gci_affiliation_id, 'Live UUID, gene or affiliation no longer matches GCI.');
        $this->check(Curation::withTrashed()->where('gdm_uuid', $r->gci_gdm_uuid)->count() === 1, 'Live UUID is duplicated or ambiguous (including deleted curations).');
        $diseases = Disease::where('mondo_id', $r->gci_mondo_id)->where('is_obsolete', false)->get();
        $mois = ModeOfInheritance::where('hp_id', $r->gci_moi_hp_id)->get();
        $this->check($r->gci_mondo_id && $diseases->count() === 1, 'MONDO mapping is missing, obsolete or ambiguous.');
        $this->check($r->gci_moi_hp_id && $mois->count() === 1, 'MoI mapping is missing or ambiguous.');
        $values = ['mondo_id' => $diseases->first()->mondo_id, 'moi_id' => $mois->first()->id];
        $changes = array_filter($values, fn ($value, $key) => (string) $value !== (string) $c->$key, ARRAY_FILTER_USE_BOTH);
        $this->check((bool) $changes, 'Live GT disease/MoI already match GCI; no GT change needed.');
        return ['id' => $r->id, 'gt_curation_id' => $c->id, 'source_type' => $r->source_type, 'changes' => $changes,
            'before' => $c->only(['gene_symbol', 'gdm_uuid', 'expert_panel_id', 'affiliation_id', 'mondo_id', 'moi_id', 'curation_status_id'])];
    }

    public function inspect(iterable $rows): array
    {
        $plans = $excluded = [];
        foreach ($rows as $row) {
            try { $plans[] = $this->plan($row); }
            catch (\Throwable $e) { $excluded[] = ['id' => $row->id, 'reason' => $e->getMessage()]; }
        }
        return ['plans' => $plans, 'excluded' => $excluded];
    }

    public function apply(int $id, ?int $actor): array
    {
        try {
            return DB::transaction(function () use ($id, $actor) {
                $r = Record::lockForUpdate()->findOrFail($id);
                if ($r->execution_status === 'APPLIED') return ['id' => $id, 'status' => 'SKIPPED', 'reason' => 'Already applied.'];
                Curation::withTrashed()->where('gdm_uuid', $r->gci_gdm_uuid)->lockForUpdate()->get();
                Curation::withTrashed()->whereKey($r->gt_curation_id)->lockForUpdate()->first();
                $plan = $this->plan($r); // Revalidate immediately before writing, not just during dry-run.
                $c = Curation::findOrFail($plan['gt_curation_id']);
                app(UpdateCuration::class)->update($c, $plan['changes']);
                $c->refresh();
                foreach (['gene_symbol', 'gdm_uuid', 'expert_panel_id', 'affiliation_id', 'curation_status_id'] as $field) {
                    $this->check($c->$field == $plan['before'][$field], 'A model listener unexpectedly changed '.$field.'.');
                }
                $r->fill(['execution_status' => 'APPLIED', 'executed_by' => $actor, 'executed_at' => now(), 'execution_error' => null])->save();
                return ['id' => $id, 'status' => 'APPLIED'];
            });
        } catch (\Throwable $e) {
            $this->recordFailure($id, $actor, $e->getMessage());
            return ['id' => $id, 'status' => 'FAILED', 'reason' => $e->getMessage()];
        }
    }

    public function recordFailure(int $id, ?int $actor, string $reason): void
    {
        DB::transaction(function () use ($id, $actor, $reason) {
            $r = Record::lockForUpdate()->findOrFail($id);
            if ($r->execution_status !== 'APPLIED') {
                $r->fill(['execution_status' => 'FAILED', 'executed_by' => $actor, 'executed_at' => now(), 'execution_error' => $reason])->save();
            }
        });
    }

    private function check(bool $ok, string $reason): void
    {
        if (!$ok) throw new RuntimeException($reason);
    }
}
