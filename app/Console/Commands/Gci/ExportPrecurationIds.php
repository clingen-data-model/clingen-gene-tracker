<?php
namespace App\Console\Commands\Gci;

use App\Services\GciReconciliation\{PrecurationExport, Selection};
use Illuminate\Console\Command;

class ExportPrecurationIds extends Command
{
    protected $signature = 'gci:export-precuration-ids {--run= : Reconciliation UUID}
        {--id=* : Restrict to explicit reconciliation row IDs}
        {--output= : New JSON file path; defaults to private storage}';
    protected $description = 'Export confidently resolved GCI records with GT precuration IDs';

    public function handle(Selection $selection, PrecurationExport $export): int
    {
        try {
            $result = $export->payload($selection->query($this->option('run'), $this->option('id'))->get());
            $path = $this->option('output') ?: storage_path('app/gdmHasPreCurId-'.now()->format('Ymd-His').'-'.\Illuminate\Support\Str::random(6).'.json');
            foreach ($result['skipped'] as $skipped) $this->line('Skipped row '.$skipped['id'].': '.$skipped['reason']);
            $json = json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $handle = @fopen($path, 'x'); // Never overwrite a source file or earlier export.
            if (!$handle) throw new \RuntimeException('Cannot create new export file: '.$path);
            try {
                if (fwrite($handle, $json) !== strlen($json)) throw new \RuntimeException('Could not write the complete export file.');
            } catch (\Throwable $e) {
                fclose($handle);
                unlink($path); // Remove only the incomplete file created by this invocation.
                throw $e;
            }
            fclose($handle);
            $this->info('Exported '.count($result['ids']).' records to '.$path);
            $this->line('Skipped '.count($result['skipped']).' unresolved/ineligible records. GCI import remains a separate step.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
