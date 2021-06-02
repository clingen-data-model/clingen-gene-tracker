<?php

namespace App\Console\Commands\OneOff;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class GtGciBaselineSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one-off:baseline-gt-gci-sequence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs all commands necessary to get baseline gt/gci baseline done.';

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
        $this->info('migrating...');
        $this->call('migrate');

        $this->info('Building gci curation records.');
        $this->call('gci:build-curations');
        
        $this->info('link precurations to gci curations');
        $this->call('curations:link-gci');

        while (Queue::size() > 0) {
            $this->info('waiting for all queued jobs to finish...');
            sleep(1);
        }

        $this->info('produce baseline gt-gci integration records');
        $this->call('gci:produce-baseline', ['--truncate'=>true, '--topic'=>'gt-gci-test']);
    }
}
