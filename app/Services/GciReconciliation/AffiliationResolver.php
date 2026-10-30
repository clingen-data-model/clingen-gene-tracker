<?php
namespace App\Services\GciReconciliation;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** GCI supplies a parent ClinGen ID, not an expert-panel or internal affiliation ID. */
class AffiliationResolver
{
    public function resolve(int $clingenId, bool $lock = false): array
    {
        $parents = DB::table('affiliations')->where('clingen_id', $clingenId)
            ->when($lock, fn ($q) => $q->lockForUpdate())->get();
        if ($parents->count() !== 1) {
            throw ValidationException::withMessages(['affiliation' => 'GCI parent affiliation is missing or ambiguous.']);
        }
        $parent = $parents->first();
        $children = DB::table('affiliations')->where('parent_id', $parent->id)
            ->when($lock, fn ($q) => $q->lockForUpdate())->pluck('id');
        $panels = DB::table('expert_panels')->whereIn('affiliation_id', $children)
            ->when($lock, fn ($q) => $q->lockForUpdate())->get();
        if ($panels->count() !== 1) {
            throw ValidationException::withMessages(['affiliation' => 'GCI affiliation must resolve to exactly one Expert Panel; none or multiple were found.']);
        }
        return [
            'parent_affiliation_id' => $parent->id, 'parent_clingen_id' => $parent->clingen_id,
            'parent_name' => $parent->name, 'child_affiliation_id' => $panels->first()->affiliation_id,
            'expert_panel_id' => $panels->first()->id, 'expert_panel_name' => $panels->first()->name,
        ];
    }
}
