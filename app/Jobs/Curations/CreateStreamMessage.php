<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\DataExchange\MessageFactories\MessageFactoryInterface;
use App\StreamMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateStreamMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $topic;

    private Curation $curation;

    private string $eventType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $topic, Curation $curation, string $eventType)
    {
        $this->topic = $topic;
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
        StreamMessage::create([
            'topic' => $this->topic,
            'message' => $factory->make($this->curation, $this->eventType),
        ]);
    }
}
