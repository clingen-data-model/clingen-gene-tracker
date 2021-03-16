<?php

namespace App\Hgnc\Artisan;

use Illuminate\Console\Command;
use App\Hgnc\CustomDownloadImporter;

class ImportHgncCustomDownload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hgnc:update-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads HGNC custom download and updates gene data.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $importer = app()->make(CustomDownloadImporter::class);
        foreach ($importer->import() as $message) {
            $this->info($message);
        }
    }
}
