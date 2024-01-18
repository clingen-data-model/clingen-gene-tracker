<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Iterator;

class ClearExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-exports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes temp files created for exports.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fi = new FilesystemIterator(storage_path('exports', FilesystemIterator::SKIP_DOTS));
        foreach($fi as $spl) {
            if ($spl->getExtension() == 'csv') {
                unlink($spl->getRealPath());
            }
        }
    }

}
