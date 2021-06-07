<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\StreamMessage;
use GuzzleHttp\Psr7\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\DataExchange\MessageFactories\MessageFactoryInterface;

class CreatePrecurationStreamMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;

    protected $eventType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, $eventType)
    {
        $this->curation = $curation;
        $this->eventType = $eventType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MessageFactoryInterface $factory)
    {
        $job = new CreateStreamMessage(
                    config('dx.topics.outgoing.precuration-events'), 
                    $this->curation, 
                    $this->eventType
                );
        Bus::dispatchNow($job);
    }
}
