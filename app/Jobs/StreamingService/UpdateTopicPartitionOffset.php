<?php

namespace App\Jobs\StreamingService;

use App\Jobs\SetState;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateTopicPartitionOffset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $topic;

    protected $partition;

    protected $offset;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($topic, $partition, $offset)
    {
        //
        $this->topic = $topic;
        $this->partition = $partition;
        $this->offset = $offset;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $varName = $this->topic.'-'.$this->partition.'-offset';
        SetState::dispatch($varName, $this->offset, 'integer');
    }
}
