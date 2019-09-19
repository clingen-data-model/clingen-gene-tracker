<?php

namespace App\Console\Commands;

use App\Curation;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curation\AugmentWithHgncInfo;
use Illuminate\Console\Command;

class AddHgncInfoToCurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:add-hgnc-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds HGNC name and id to existing curation records';

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
        $curations = Curation::all();
        $bar = $this->output->createProgressBar($curations->count());
        $curations->each(function ($curation) use ($bar) {
            try {
                AugmentWithHgncInfo::dispatch($curation);
            } catch (HttpNotFoundException $e) {
                \Log::warning($e->getMessage());
                dump($e->getMessage());
            }
            $bar->advance();
        }); 
    }
}
