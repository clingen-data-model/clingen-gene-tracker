<?php

namespace App\Listeners\Curations;

use App\DataExchange\MessageFactories\MessageFactoryInterface;
use App\Events\Curation\Updated;
use App\Jobs\Curations\CreatePrecurationStreamMessage;

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
     * @return void
     */
    public function handle(Updated $event): void
    {
        \Bus::dispatch(new CreatePrecurationStreamMessage($event->curation, 'updated'));
    }
}
