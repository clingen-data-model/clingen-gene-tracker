<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateClingenLabel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-clingen-label {--path=mondo/clingen_preferred_label.tsv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ClinGen Preferred Label TSV for Public/Mondo (clingen_preferred_label.tsv)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = Storage::disk('public');
        $path = $this->option('path');

        $headers = [
            'mondo_id',
            'xref_genevalidation',
            'xref_source',
            'see_also',
            'see_also_source',
            'clingen_label',
            'clingen_label_type',
            'clingen_label_source',
            'subset',
            'subset_source',
        ];
        $tmp = storage_path('app/clingen_preferred_label.tsv');
        @mkdir(dirname($tmp), 0775, true);

        $fh = fopen($tmp, 'wb');
        if (!$fh) {
            $this->error('Unable to create temp TSV.');
            return self::FAILURE;
        }

        // Header
        fwrite($fh, implode("\t", $headers) . "\n");

        DB::table('mondo_clingen_label')
            ->orderBy('mondo_id')
            ->chunk(500, function ($rows) use ($fh, $headers) {
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($headers as $col) {
                        $val = (string)($row->$col ?? '');
                        $val = str_replace(["\t", "\r\n", "\n", "\r"], [' ', ' ', ' ', ' '], $val);

                        $vals[] = $val;
                    }
                    fwrite($fh, implode("\t", $vals) . "\n");
                }
            });

        fclose($fh);

        $disk->put($path, file_get_contents($tmp));
        @unlink($tmp);

        $this->info("Generated: storage/app/public/{$path}");
        return self::SUCCESS;
    }
}
