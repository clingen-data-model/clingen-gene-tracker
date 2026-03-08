<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MondoSyncNtrTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mondo:sync-ntr-template {--archive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download and cache the MONDO NTR Jinja template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ref = config('mondo.template.ref');
        $path = config('mondo.template.path');
        $cachePath = config('mondo.template.cache_path');

        $url = "https://raw.githubusercontent.com/monarch-initiative/mondo/{$ref}/{$path}";

        $this->info("Downloading template from: {$url}");

        $resp = Http::timeout(15)->get($url);

        if (!$resp->successful()) {
            $this->error("Failed to download template (HTTP {$resp->status()})");
            return self::FAILURE;
        }

        $content = $resp->body();
        Storage::disk('local')->put($cachePath, $content);
        if ($this->option('archive')) {
            $ts = now()->format('Ymd_His');
            $archivePath = "mondo/templates/archive/monogenic_ntr.{$ref}.{$ts}.md.j2";
            Storage::disk('local')->put($archivePath, $content);
            $this->info("Archived: storage/app/{$archivePath}");
        }

        Storage::disk('local')->put('mondo/templates/manifest.json', json_encode([
            'ref' => $ref,
            'path' => $path,
            'fetched_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        $this->info("Cached: storage/app/{$cachePath}");
        return self::SUCCESS;
    }
}
