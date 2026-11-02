<?php
namespace App\Services\GciReconciliation;

use App\Curation;
use Illuminate\Support\Facades\DB;

/** Live values are response data only: reconciliation findings are never synchronized. */
class Comparison
{
    public function current(Record $row): ?Curation
    {
        return Curation::withTrashed()->with(['expertPanel.affiliation.parent', 'modeOfInheritance', 'currentStatus'])
            ->find($row->resolved_gt_curation_id ?: $row->gt_curation_id);
    }

    public function describe(Record $row): array
    {
        $c = $this->current($row);
        $parent = $c?->expertPanel?->affiliation?->parent;
        $live = $c ? [
            'curation_id' => $c->id, 'gdm_uuid' => $c->gdm_uuid, 'gene_symbol' => $c->gene_symbol,
            'mondo_id' => $c->mondo_id, 'moi_hp_id' => $c->modeOfInheritance?->hp_id,
            'affiliation_id' => $parent?->clingen_id, 'affiliation_name' => $parent?->name,
            'expert_panel_id' => $c->expert_panel_id, 'expert_panel_name' => $c->expertPanel?->name,
            'stored_parent_affiliation_id' => $c->affiliation_id,
            'status_id' => $c->curation_status_id, 'status_name' => $c->currentStatus?->name,
            'deleted_at' => $c->deleted_at, 'archived_at' => $c->archived_at,
            'is_retired' => $c->curation_status_id == config('curations.statuses.retired-assignment'),
        ] : null;
        $flags = [
            'precuration_id_match' => $row->gci_precuration_id && $c ? $row->gci_precuration_id == $c->id : null,
            'gdm_uuid_match' => $c ? strtolower((string) $c->gdm_uuid) === $row->gci_gdm_uuid : null,
            'gene_match' => $c ? strtoupper($c->gene_symbol) === $row->gci_gene_symbol : null,
            'affiliation_match' => $c ? $parent && $parent->clingen_id == $row->gci_affiliation_id : null,
            'disease_match' => $row->gci_mondo_id && $c ? $row->gci_mondo_id === $c->mondo_id : null,
            'moi_match' => $row->gci_moi_hp_id && $c ? $row->gci_moi_hp_id === $c->modeOfInheritance?->hp_id : null,
        ];
        $unique = $c && $flags['gdm_uuid_match'] && Curation::withTrashed()->where('gdm_uuid', $row->gci_gdm_uuid)->count() === 1;
        $active = $c && !$c->trashed() && !$c->archived_at && !$live['is_retired'];
        $identity = $active && $unique && $flags['gene_match'] && $flags['affiliation_match']
            && ($row->source_type === PayloadReader::NO || $flags['precuration_id_match']);
        return ['current_gt' => $live, 'current_comparison' => $flags,
            'current_identity_matches' => (bool) $identity,
            'current_matches_gci' => (bool) ($identity && $flags['disease_match'] && $flags['moi_match']),
            'gci_action' => $row->source_type === PayloadReader::NO && $identity ? Decision::EXPORT : null];
    }

    public function candidates(Record $row): array
    {
        if ($row->match_status !== 'NOT_FOUND_IN_GT') return [];
        return DB::table('curations as c')->join('expert_panels as ep', 'ep.id', '=', 'c.expert_panel_id')
            ->join('affiliations as a', 'a.id', '=', 'ep.affiliation_id')->join('affiliations as p', 'p.id', '=', 'a.parent_id')
            ->whereNull('c.deleted_at')->where('c.gene_symbol', $row->gci_gene_symbol)->where('p.clingen_id', $row->gci_affiliation_id)
            ->select(['c.id', 'c.gdm_uuid', 'c.mondo_id', 'c.moi_id', 'c.curation_status_id', 'c.archived_at'])->orderBy('c.id')->get()->toArray();
    }
}
