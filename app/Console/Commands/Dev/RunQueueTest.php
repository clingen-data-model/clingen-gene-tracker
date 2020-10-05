<?php

namespace App\Console\Commands\Dev;

use App\Jobs\Dev\TestQueueProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Queue;

class RunQueueTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:test-queue  {--count=: number of times to send something to the queue.}';

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
    public function handle()
    {
        $startMsg = 'queue size at start: '.Queue::size();
        Log::debug($startMsg);
        $this->info($startMsg);
        // dump((int) $this->options('count'));
        $count = $this->option('count') ?? 1;
        for ($i = 0; $i < $count; ++$i) {
            // dump($i);
            TestQueueProcess::dispatch();
        }
        $endMsg = 'queue size at end: '.Queue::size();
        Log::debug($endMsg);
        $this->info($endMsg);
    }
}
