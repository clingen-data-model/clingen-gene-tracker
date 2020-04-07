<?php

namespace App\Console\Commands\StreamingService;

use Illuminate\Console\Command;
use App\Contracts\MessageConsumer;
use App\Contracts\GeneValidityCurationUpdateJob;
use App\Jobs\DryRunUpdateFromGeneValidityMessage;

class ConsumeGeneValidityEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gci:consume {--dry-run : dry run only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(MessageConsumer $consumer)
    {
        if ($this->option('dry-run')) {
            $this->info('Performing dry-run.  No curations will  be updated.')
            app()->bind(GeneValidityCurationUpdateJob::class, DryRunUpdateFromGeneValidityMessage::class);
        }
        $consumer->listen();
    }
}
