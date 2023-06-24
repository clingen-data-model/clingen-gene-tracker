<?php

namespace App\Listeners\Curations;

use App\DataExchange\MessageFactories\MessageFactoryInterface;
use App\Events\Curation\Created;
use App\Jobs\Curations\CreatePrecurationStreamMessage;

class MakeCurationCreatedStreamMessage
{
    private $factory;

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
    public function handle(Created $event)
    {
        \Bus::dispatch(new CreatePrecurationStreamMessage($event->curation, 'created'));
    }
}
