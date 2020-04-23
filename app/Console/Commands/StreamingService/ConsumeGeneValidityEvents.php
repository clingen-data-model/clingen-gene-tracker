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
    protected $signature = 'gci:consume {--dry-run : dry run only} {--topic=gene_validity_events} {--message-number= : number of messages to listen for}';

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
            app()->bind(GeneValidityCurationUpdateJob::class, DryRunUpdateFromGeneValidityMessage::class);
        }

        $consumer->addTopic($this->option('topic'));
        $this->info('listening to '.implode(', ', $consumer->topics));
        if ($this->option('message-number')) {
            $consumer->consumeSomeMessages((integer)$this->option("message-number"));
            return;
        }
        $consumer->listen();
    }
}
