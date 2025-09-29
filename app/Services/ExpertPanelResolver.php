<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ExpertPanelResolver
{
    /**
     * Ensure there's an expert_panels row for a given ClinGen affiliation id.
     * If missing, auto-create using affiliations.name + a source suffix.
     *
     * @return array{expert_panel_id:int, affiliation:object, created:bool}
     *
     * Throws \RuntimeException if the affiliation does not exist.
     * Used by client API controllers BulkUploadApiController & PanelMembersApiController.
     */
    public function ensureForClinGen(int $clingenId, string $sourceLabel = 'Auto-created'): array
    {
        // 1. Resolve affiliation row
        $aff = DB::table('affiliations')->where('clingen_id', $clingenId)->first();
        if (!$aff) { throw new \RuntimeException("Affiliation not found for ClinGen ID {$clingenId}"); }

        // 2. Return existing expert panel if present
        $epId = DB::table('expert_panels')->where('affiliation_id', $aff->id)->value('id');
        if ($epId) { return ['expert_panel_id' => (int) $epId, 'affiliation' => $aff, 'created' => false]; }

        // 3. Create a new expert panel (handle races with unique index or retry)
        $name = trim(($aff->name ?? 'Expert Panel') . " (Auto-created via {$sourceLabel})");

        try {
            $newId = DB::table('expert_panels')->insertGetId([
                'name'           => $name,
                'affiliation_id' => $aff->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return ['expert_panel_id' => (int) $newId, 'affiliation' => $aff, 'created' => true];
        } catch (QueryException $e) {
            $existing = DB::table('expert_panels')->where('affiliation_id', $aff->id)->value('id');
            if ($existing) {
                return ['expert_panel_id' => (int) $existing, 'affiliation' => $aff, 'created' => false];
            }
            throw $e;
        }
    }
}
