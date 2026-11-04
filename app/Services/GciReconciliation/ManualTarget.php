<?php
namespace App\Services\GciReconciliation;

use App\Curation;
use UnexpectedValueException;

/** Explicit review can resolve historical ambiguity; automated identity stays strict. */
class ManualTarget
{
    public function selected(Record $row): bool
    {
        return $row->review_status === 'REVIEWED' && (bool) $row->resolved_gt_curation_id
            && in_array($row->resolution, [Decision::MATCH, Decision::CREATE, Decision::EXPORT], true);
    }

    public function validate(Record $row, ?Curation $target = null): Curation
    {
        $this->check($this->selected($row), 'No explicitly reviewed GT target.');
        $target ??= Curation::withTrashed()->with(['expertPanel.affiliation.parent', 'modeOfInheritance'])->find($row->resolved_gt_curation_id);
        $this->check($target && $target->id == $row->resolved_gt_curation_id && $this->active($target), 'Reviewed GT target is missing, deleted, archived or retired.');
        $this->check(strtoupper($target->gene_symbol) === $row->gci_gene_symbol
            && $target->expertPanel?->affiliation?->parent?->clingen_id == $row->gci_affiliation_id,
            'Reviewed GT gene or affiliation differs from GCI.');
        $owners = Curation::withTrashed()->where('gdm_uuid', $row->gci_gdm_uuid)->get();
        $this->check(!$owners->contains(fn ($owner) => $owner->id != $target->id && $this->active($owner)), 'Another active GT curation owns the GCI UUID.');
        // Preserve existing manual association of an unlinked NO-source target, only
        // when the UUID has no owner at all. HAS-source corrections must own it.
        $unlinked = $row->source_type === PayloadReader::NO && !$target->gdm_uuid && $owners->isEmpty();
        $this->check($row->gci_gdm_uuid && (strtolower((string) $target->gdm_uuid) === $row->gci_gdm_uuid || $unlinked), 'Reviewed GT UUID differs from GCI.');
        return $target;
    }

    private function active(Curation $target): bool
    {
        return !$target->trashed() && !$target->archived_at
            && $target->curation_status_id != config('curations.statuses.retired-assignment');
    }

    private function check(bool $ok, string $reason): void
    {
        if (!$ok) throw new UnexpectedValueException($reason);
    }
}
