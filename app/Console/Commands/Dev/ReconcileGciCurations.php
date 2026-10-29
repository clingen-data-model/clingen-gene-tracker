<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReconcileGciCurations extends Command
{
    protected $signature = 'gci:reconcile-curations
        {hasPrecurationFile : GCI JSON export containing precurationId}
        {noPrecurationFile : GCI JSON export without precurationId}
        {--label= : Optional label for this reconciliation run}
        {--export-dir= : Optional export directory}';

    protected $description = 'Reconcile GCI curations with GT using precuration ID first, then GDM UUID';

    private const SOURCE_HAS_PRECURATION = 'HAS_PRECURATION_ID';
    private const SOURCE_NO_PRECURATION = 'NO_PRECURATION_ID';

    private const METHOD_PRECURATION_ID = 'PRECURATION_ID';
    private const METHOD_GDM_UUID = 'GDM_UUID';

    private const STATUS_CONFIRMED = 'CONFIRMED';
    private const STATUS_NOT_FOUND = 'NOT_FOUND_IN_GT';
    private const STATUS_AMBIGUOUS = 'AMBIGUOUS_GDM_UUID';
    private const STATUS_ALREADY_RECONCILED = 'ALREADY_RECONCILED';
    private const STATUS_CONFLICT = 'CONFLICT';

    public function handle()
    {
        $hasPrecurationPath = $this->resolveInputPath($this->argument('hasPrecurationFile'));
        $noPrecurationPath = $this->resolveInputPath($this->argument('noPrecurationFile'));

        $runUuid = (string) Str::uuid();
        $runLabel = $this->option('label');

        try {
            app(\App\Services\GciReconciliation\Reconciler::class)->reconcile(
                $runUuid, $runLabel, $hasPrecurationPath, $noPrecurationPath
            );

            $summary = $this->buildSummary($runUuid);
            $exportDirectory = $this->exportReports($runUuid, $summary);

            $this->displaySummary($runUuid, $summary, $exportDirectory);

            return 0;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    private function buildSummary(string $runUuid): array
    {
        $base = DB::table('gci_gt_reconciliations')->where('run_uuid', $runUuid);

        return [
            'total_records' => (clone $base)->count(),

            'has_precuration_id' => [
                'total' => (clone $base)
                    ->where('source_type', self::SOURCE_HAS_PRECURATION)
                    ->count(),
                'confirmed_by_precuration_id' => (clone $base)
                    ->where('source_type', self::SOURCE_HAS_PRECURATION)
                    ->where('match_status', self::STATUS_CONFIRMED)
                    ->where('match_method', self::METHOD_PRECURATION_ID)
                    ->count(),
                'confirmed_by_gdm_uuid_fallback' => (clone $base)
                    ->where('source_type', self::SOURCE_HAS_PRECURATION)
                    ->where('match_status', self::STATUS_CONFIRMED)
                    ->where('match_method', self::METHOD_GDM_UUID)
                    ->count(),
                'not_found_in_gt' => (clone $base)
                    ->where('source_type', self::SOURCE_HAS_PRECURATION)
                    ->where('match_status', self::STATUS_NOT_FOUND)
                    ->count(),
                'ambiguous_gdm_uuid' => (clone $base)
                    ->where('source_type', self::SOURCE_HAS_PRECURATION)
                    ->where('match_status', self::STATUS_AMBIGUOUS)
                    ->count(),
            ],

            'no_precuration_id' => [
                'total' => (clone $base)
                    ->where('source_type', self::SOURCE_NO_PRECURATION)
                    ->count(),
                'confirmed_by_gdm_uuid' => (clone $base)
                    ->where('source_type', self::SOURCE_NO_PRECURATION)
                    ->where('match_status', self::STATUS_CONFIRMED)
                    ->count(),
                'not_found_in_gt' => (clone $base)
                    ->where('source_type', self::SOURCE_NO_PRECURATION)
                    ->where('match_status', self::STATUS_NOT_FOUND)
                    ->count(),
                'ambiguous_gdm_uuid' => (clone $base)
                    ->where('source_type', self::SOURCE_NO_PRECURATION)
                    ->where('match_status', self::STATUS_AMBIGUOUS)
                    ->count(),
                'already_reconciled' => (clone $base)
                    ->where('source_type', self::SOURCE_NO_PRECURATION)
                    ->where('match_status', self::STATUS_ALREADY_RECONCILED)
                    ->count(),
            ],

            'confirmed' => [
                'total' => (clone $base)->where('is_confirmed_match', 1)->count(),
                'all_fields_match' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('all_compared_fields_match', 1)
                    ->count(),
                'identity_warnings' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('has_identity_warning', 1)
                    ->count(),
                'needs_gt_update' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('needs_gt_update', 1)
                    ->count(),
                'safe_update_candidates' => (clone $base)
                    ->where('safe_update_candidate', 1)
                    ->count(),
                'gdm_uuid_mismatch' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('gdm_uuid_match', 0)
                    ->count(),
                'gene_mismatch' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('gene_match', 0)
                    ->count(),
                'affiliation_mismatch' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('affiliation_match', 0)
                    ->count(),
                'disease_mismatch' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('disease_match', 0)
                    ->count(),
                'moi_mismatch' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('moi_match', 0)
                    ->count(),
                'freetext_disease' => (clone $base)
                    ->where('is_confirmed_match', 1)
                    ->where('gci_freetext_disease', 1)
                    ->count(),
            ],
        ];
    }

    private function exportReports(string $runUuid, array $summary): string
    {
        $directory = $this->resolveExportDirectory($runUuid);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put(
            $directory.'/summary.json',
            json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $rows = DB::table('gci_gt_reconciliations')
            ->where('run_uuid', $runUuid)
            ->orderBy('source_type')
            ->orderBy('source_row_number')
            ->get();

        $this->writeCsv($directory.'/all-records.csv', $rows);
        $this->writeCsv(
            $directory.'/has-precuration-id.csv',
            $rows->where('source_type', self::SOURCE_HAS_PRECURATION)
        );
        $this->writeCsv(
            $directory.'/no-precuration-id.csv',
            $rows->where('source_type', self::SOURCE_NO_PRECURATION)
        );
        $this->writeCsv(
            $directory.'/confirmed-matches.csv',
            $rows->where('is_confirmed_match', 1)
        );
        $this->writeCsv(
            $directory.'/needs-gt-update.csv',
            $rows->where('needs_gt_update', 1)
        );
        $this->writeCsv(
            $directory.'/safe-update-candidates.csv',
            $rows->where('safe_update_candidate', 1)
        );
        $this->writeCsv(
            $directory.'/identity-warnings.csv',
            $rows->where('has_identity_warning', 1)
        );
        $this->writeCsv(
            $directory.'/not-found-in-gt.csv',
            $rows->where('match_status', self::STATUS_NOT_FOUND)
        );
        $this->writeCsv(
            $directory.'/ambiguous-gdm-uuid.csv',
            $rows->where('match_status', self::STATUS_AMBIGUOUS)
        );

        return $directory;
    }

    private function writeCsv(string $path, Collection $rows): void
    {
        $headers = [
            'run_uuid',
            'run_label',
            'source_type',
            'source_filename',
            'source_row_number',
            'gci_precuration_id',
            'gci_gdm_uuid',
            'gci_gene_symbol',
            'gci_affiliation_id',
            'gci_disease_id',
            'gci_mondo_id',
            'gci_moi_hp_id',
            'gci_freetext_disease',
            'gt_curation_id',
            'gt_uuid',
            'gt_gdm_uuid',
            'gt_gene_symbol',
            'gt_affiliation_id',
            'gt_expert_panel_id',
            'gt_expert_panel_name',
            'gt_mondo_id',
            'gt_moi_hp_id',
            'gt_curation_status_id',
            'gt_archived_at',
            'match_method',
            'match_status',
            'is_confirmed_match',
            'precuration_id_match',
            'gdm_uuid_match',
            'gene_match',
            'affiliation_match',
            'disease_match',
            'moi_match',
            'all_compared_fields_match',
            'gdm_uuid_duplicate_in_gt',
            'precuration_id_exists_deleted',
            'has_identity_warning',
            'needs_gt_update',
            'needs_gdm_uuid_update',
            'needs_disease_update',
            'needs_moi_update',
            'safe_update_candidate',
            'conflicting_gt_curation_ids',
            'issue_codes',
            'review_status',
            'review_notes',
            'resolution',
        ];

        $handle = fopen($path, 'w');

        if (!$handle) {
            throw new RuntimeException('Unable to create CSV file: '.$path);
        }

        try {
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                $values = [];

                foreach ($headers as $header) {
                    $values[] = $row->{$header};
                }

                fputcsv($handle, $values);
            }
        } finally {
            fclose($handle);
        }
    }

    private function displaySummary(string $runUuid, array $summary, string $exportDirectory): void
    {
        $this->newLine();
        $this->info('GCI/GT reconciliation completed.');

        $this->table(
            ['Category', 'Count'],
            [
                ['Run UUID', $runUuid],
                ['GCI records with precuration ID', $summary['has_precuration_id']['total']],
                ['Confirmed by precuration ID', $summary['has_precuration_id']['confirmed_by_precuration_id']],
                ['Confirmed by GDM UUID fallback', $summary['has_precuration_id']['confirmed_by_gdm_uuid_fallback']],
                ['Has-precuration records not found in GT', $summary['has_precuration_id']['not_found_in_gt']],
                ['Has-precuration ambiguous GDM UUID', $summary['has_precuration_id']['ambiguous_gdm_uuid']],
                ['GCI records without precuration ID', $summary['no_precuration_id']['total']],
                ['Confirmed by GDM UUID', $summary['no_precuration_id']['confirmed_by_gdm_uuid']],
                ['No-precuration records not found in GT', $summary['no_precuration_id']['not_found_in_gt']],
                ['No-precuration ambiguous GDM UUID', $summary['no_precuration_id']['ambiguous_gdm_uuid']],
                ['Already reconciled in first pass', $summary['no_precuration_id']['already_reconciled']],
                ['Total confirmed matches', $summary['confirmed']['total']],
                ['Confirmed with all compared fields matching', $summary['confirmed']['all_fields_match']],
                ['Confirmed with identity warning', $summary['confirmed']['identity_warnings']],
                ['Confirmed needing GT update', $summary['confirmed']['needs_gt_update']],
                ['Safe update candidates', $summary['confirmed']['safe_update_candidates']],
                ['GDM UUID mismatches', $summary['confirmed']['gdm_uuid_mismatch']],
                ['Gene mismatches', $summary['confirmed']['gene_mismatch']],
                ['Affiliation mismatches', $summary['confirmed']['affiliation_mismatch']],
                ['Disease mismatches', $summary['confirmed']['disease_mismatch']],
                ['MOI mismatches', $summary['confirmed']['moi_mismatch']],
                ['GCI free-text diseases', $summary['confirmed']['freetext_disease']],
            ]
        );

        $this->newLine();
        $this->line('Reports: '.$exportDirectory);
    }

    private function resolveInputPath(string $file): string
    {
        if (File::exists($file)) {
            return realpath($file) ?: $file;
        }

        $storagePath = storage_path('app/'.ltrim($file, '/\\'));

        if (File::exists($storagePath)) {
            return realpath($storagePath) ?: $storagePath;
        }

        throw new RuntimeException('GCI input file not found: '.$file);
    }

    private function resolveExportDirectory(string $runUuid): string
    {
        $option = $this->option('export-dir');

        if (!$option) {
            return storage_path('app/gci-reconciliation/'.$runUuid);
        }

        if ($this->isAbsolutePath($option)) {
            return rtrim($option, '/\\');
        }

        return storage_path('app/'.trim($option, '/\\'));
    }

    private function isAbsolutePath(string $path): bool
    {
        return strpos($path, '/') === 0
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

}
