<?php
namespace App\Services\GciReconciliation;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory as Excel;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;

/** Reporting only. Uses the same live comparison as the admin detail view. */
class CuratorReviewExport
{
    public function unresolved(Record $row, array $live): bool
    {
        if (in_array($row->execution_status, ['APPLIED', 'NO_ACTION', 'RESOLVED_MANUALLY'], true)) return false;
        if ($row->review_status !== 'REVIEWED') return true;
        if (in_array($row->resolution, array_merge(Decision::NONE, Decision::GCI_ACTIONS), true)) return false;
        return !$live['current_matches_gci'];
    }

    public function sheets(iterable $records): array
    {
        $rows = collect($records);
        $data = new ExportGtData($rows, true);
        $comparison = app(Comparison::class);
        $headers = ['Reconciliation ID', 'Run UUID', 'GCI UUID', 'GCI Precuration ID', 'Gene', 'GCI Disease', 'GCI MoI',
            'GCI Affiliation ID', 'Original GT Curation ID', 'Original GT UUID', 'Original GT Gene', 'Original GT Disease',
            'Original GT MoI', 'Original GT Affiliation ID', 'Original GCEP Name', 'Resolved GT Curation ID',
            'Current GT Curation ID', 'Current GT UUID', 'Current GT Gene', 'Current GT Disease', 'Current GT MoI',
            'Current GT Affiliation ID', 'Current GCEP Name', 'GT Status', 'Candidate GT IDs', 'Candidate GT UUIDs / Status',
            'Original Match Status', 'Original Issue Codes', 'Current Comparison', 'Review Status', 'Current Resolution',
            'Execution Status', 'Review Notes', 'Review Question / Suggested Action'];
        $sheets = array_fill_keys(['GCI Not Found in GT', 'Ambiguous - Identity Conflict', 'Inactive GT Records', 'Other Unresolved'], [$headers]);
        $counts = array_fill_keys(['GCI Not Found in GT', 'Ambiguous GDM UUID', 'Deleted / Archived / Retired GT target', 'Identity / Precuration ID conflict', 'Other unresolved'], 0);
        foreach ($rows as $row) {
            $live = $comparison->describe($row, $data);
            if (!$this->unresolved($row, $live)) continue;
            $gt = $live['current_gt'] ?? [];
            $candidates = collect(array_merge($row->conflicting_gt_curation_ids ?? [], $row->deleted_gt_curation_ids ?? []))
                ->merge($data->owners($row->gci_gdm_uuid)->pluck('id'))->unique()->values();
            $inactive = $gt && ($gt['deleted_at'] || $gt['archived_at'] || $gt['is_retired']);
            // No selected target: retain inactive candidate associations for investigation.
            if (!$gt && $candidates->isNotEmpty()) {
                $inactive = $candidates->every(function ($id) use ($data, $row) {
                    $c = $data->target($id) ?? $data->owners($row->gci_gdm_uuid)->firstWhere('id', $id);
                    return $c && ($c->deleted_at || $c->archived_at || $c->curation_status_id == config('curations.statuses.retired-assignment'));
                });
            }
            $status = $gt ? implode('; ', array_filter([$gt['status_name'], $gt['deleted_at'] ? 'Deleted' : null, $gt['archived_at'] ? 'Archived' : null, $gt['is_retired'] ? 'Retired' : null])) : 'No current GT target';
            $activeOwners = $data->owners($row->gci_gdm_uuid)->filter(fn ($c) => !$c->deleted_at && !$c->archived_at && $c->curation_status_id != config('curations.statuses.retired-assignment'));
            $ambiguous = !$gt && ($activeOwners->count() > 1 || $row->match_status === 'AMBIGUOUS_GDM_UUID');
            if ($inactive) {
                $sheet = 'Inactive GT Records'; $category = 'Deleted / Archived / Retired GT target'; $question = 'Review inactive GT association';
            } elseif (!$gt && !$ambiguous && $row->match_status === 'NOT_FOUND_IN_GT') {
                $sheet = $category = 'GCI Not Found in GT'; $question = 'Identify the correct existing GT curation or determine whether a new GT curation is needed';
            } elseif ($ambiguous || ($gt && !$live['current_identity_matches']) || (!$gt && ($row->has_identity_warning || in_array($row->match_status, ['CONFLICT', 'ALREADY_RECONCILED'])))) {
                $sheet = 'Ambiguous - Identity Conflict'; $category = $ambiguous ? 'Ambiguous GDM UUID' : 'Identity / Precuration ID conflict';
                $question = 'Verify the correct GT curation and whether the GCI precuration ID should be corrected';
            } elseif (!$gt) {
                $sheet = $category = 'GCI Not Found in GT'; $question = 'Identify the correct existing GT curation or determine whether a new GT curation is needed';
            } else {
                $sheet = 'Other Unresolved'; $category = 'Other unresolved'; $question = 'Review current differences and record a decision';
            }
            $counts[$category]++;
            $flags = collect($live['current_comparison'])->map(fn ($value, $key) => str_replace('_', ' ', $key).': '.($value === null ? 'Not comparable' : ($value ? 'Match' : 'Mismatch')))->implode('; ');
            $candidateDetails = $candidates->map(function ($id) use ($data, $row) {
                $c = $data->target($id) ?? $data->owners($row->gci_gdm_uuid)->firstWhere('id', $id);
                return $id.': '.($c?->gdm_uuid ?: 'No UUID').' / '.(!$c ? 'Missing' : ($c->deleted_at ? 'Deleted' : ($c->archived_at ? 'Archived' : ($c->curation_status_id == config('curations.statuses.retired-assignment') ? 'Retired' : 'Active'))));
            })->implode('; ');
            $sheets[$sheet][] = [$row->id, $row->run_uuid, $row->gci_gdm_uuid, $row->gci_precuration_id, $row->gci_gene_symbol,
                $row->raw_payload['disease'] ?? $row->gci_mondo_id, $row->raw_payload['modeInheritance'] ?? $row->gci_moi_hp_id,
                $row->gci_affiliation_id, $row->gt_curation_id, $row->gt_gdm_uuid, $row->gt_gene_symbol, $row->gt_mondo_id,
                $row->gt_moi_hp_id, $row->gt_affiliation_id, $row->gt_expert_panel_name, $row->resolved_gt_curation_id,
                $gt['curation_id'] ?? null, $gt['gdm_uuid'] ?? null, $gt['gene_symbol'] ?? null, $gt['mondo_id'] ?? null,
                $gt['moi_hp_id'] ?? null, $gt['affiliation_id'] ?? null, $gt['expert_panel_name'] ?? null, $status,
                $candidates->implode(', '), $candidateDetails, $row->match_status, implode('; ', $row->issue_codes ?? []),
                $flags, $row->review_status, $row->resolution, $row->execution_status, $row->review_notes, $question];
        }
        $summary = [['Curator review', 'Count / Value'], ['Total unresolved', array_sum($counts)]];
        foreach ($counts as $label => $count) $summary[] = [$label, $count];
        $summary[] = ['Export timestamp', now()->toIso8601String()];
        foreach ($rows->pluck('run_uuid')->unique() as $run) $summary[] = ['Reconciliation run UUID', $run];
        return ['Summary' => $summary] + $sheets;
    }

    public function write(array $sheets, string $path): void
    {
        $writer = Excel::createXLSXWriter();
        $writer->openToFile($path);
        try {
            $first = true;
            foreach ($sheets as $name => $rows) {
                $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $first = false;
                $sheet->setName($name);
                foreach ($rows as $index => $values) $writer->addRow(Excel::createRowFromArray($values,
                    $index === 0 ? (new StyleBuilder())->setFontBold()->build() : null));
            }
        } finally { $writer->close(); }
    }
}
