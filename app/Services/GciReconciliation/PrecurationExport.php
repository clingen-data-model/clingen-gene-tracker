<?php
namespace App\Services\GciReconciliation;

use App\Curation;
use RuntimeException;

class PrecurationExport
{
    public function payload(iterable $rows): array
    {
        $items = $ids = $skipped = $seen = [];
        foreach ($rows as $r) {
            try {
                $this->check($r->source_type === PayloadReader::NO, 'Record already has a GCI precuration ID.');
                $this->check($r->review_status !== 'NEEDS_FURTHER_REVIEW'
                    && !in_array($r->resolution, array_merge(Decision::NONE, Decision::GCI_ACTIONS, ['NEEDS_FURTHER_REVIEW']), true)
                    && $r->execution_status !== 'FAILED', 'Unresolved review, failed action, or no-action decision.');
                $direct = $r->match_status === 'CONFIRMED' && $r->match_method === 'GDM_UUID'
                    && !$r->has_identity_warning && !$r->gdm_uuid_duplicate_in_gt && $r->gene_match && $r->affiliation_match
                    && in_array($r->resolution, [null, Decision::UPDATE, Decision::EXPORT], true)
                    && (!$r->resolved_gt_curation_id || $r->resolved_gt_curation_id == $r->gt_curation_id);
                $manual = $r->review_status === 'REVIEWED' && $r->resolved_gt_curation_id
                    && in_array($r->resolution, [Decision::MATCH, Decision::CREATE, Decision::EXPORT], true);
                $this->check($direct || $manual, 'Identity has not been confidently resolved.');
                $c = Curation::with(['expertPanel.affiliation.parent'])->find($manual ? $r->resolved_gt_curation_id : $r->gt_curation_id);
                $this->check($c && !$c->archived_at && $c->curation_status_id != config('curations.statuses.retired-assignment'), 'GT target is missing, deleted, archived or retired.');
                $this->check(strtoupper($c->gene_symbol) === $r->gci_gene_symbol
                    && $c->expertPanel?->affiliation?->parent?->clingen_id == $r->gci_affiliation_id, 'Live gene or affiliation differs from GCI.');
                $owners = Curation::withTrashed()->where('gdm_uuid', $r->gci_gdm_uuid)->pluck('id');
                $this->check($owners->count() <= 1 && (!$owners->count() || $owners->first() == $c->id), 'GDM UUID is ambiguous or belongs to another/deleted target.');
                $this->check(strtolower((string) $c->gdm_uuid) === $r->gci_gdm_uuid || ($manual && !$c->gdm_uuid && !$owners->count()), 'GT UUID differs from GCI.');
                if (isset($seen[$r->gci_gdm_uuid])) throw new RuntimeException('Duplicate GCI UUID in export selection: '.$r->gci_gdm_uuid);
                $seen[$r->gci_gdm_uuid] = true;
                $item = array_intersect_key($r->raw_payload, array_flip(['gene', 'modeInheritance', 'affiliation', 'disease', 'PK']));
                $item['precurationId'] = (string) $c->id;
                $items[] = $item;
                $ids[] = $r->id;
            } catch (\UnexpectedValueException $e) {
                $skipped[] = ['id' => $r->id, 'reason' => $e->getMessage()];
            }
        }
        return ['payload' => ['Items' => $items, 'Count' => count($items), 'ScannedCount' => count($items), 'ConsumedCapacity' => null], 'ids' => $ids, 'skipped' => $skipped];
    }

    private function check(bool $ok, string $reason): void
    {
        if (!$ok) throw new \UnexpectedValueException($reason);
    }
}
