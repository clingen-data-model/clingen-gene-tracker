<?php

namespace App\Listeners\Curations;

use App\StreamMessage;
use App\Events\Curation\Updated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\Curations\CreatePrecurationStreamMessage;
use App\DataExchange\MessageFactories\MessageFactoryInterface;

class MakeCurationUpdatedStreamMessage
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MessageFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Handle the event.
     *
     * @param  Updated  $event
     * @return void
     */
    public function handle(Updated $event)
    {
        \Bus::dispatch(new CreatePrecurationStreamMessage($event->curation, 'updated'));
    }
}
