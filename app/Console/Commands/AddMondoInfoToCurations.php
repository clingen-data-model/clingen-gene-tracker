<?php

namespace App\Console\Commands;

use App\Curation;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curation\AugmentWithMondoInfo;
use Illuminate\Console\Command;

class AddMondoInfoToCurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:add-mondo-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Mondo Info to existing curations';

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
        $curations = Curation::whereNotNull('mondo_id')->get();
        $bar = $this->output->createProgressBar($curations->count());
        $curations->each(function ($curation) use ($bar) {
            try {
                AugmentWithMondoInfo::dispatch($curation);
            } catch (HttpNotFoundException $e) {
                \Log::warning('Mondo id '.$curation->mondo_id.' was not found via MonDO API');
                if (app()->environment('local', 'testing')) {
                    dump($e->getMessage());
                }
            }
            $bar->advance();
        });
    }
}
