<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\DataExchange\MessageFactories\MessageFactoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

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
     */
    public function handle(MessageFactoryInterface $factory): void
    {
        $job = new CreateStreamMessage(
            config('dx.topics.outgoing.precuration-events'),
            $this->curation,
            $this->eventType
        );
        Bus::dispatchSync($job);
    }
}
