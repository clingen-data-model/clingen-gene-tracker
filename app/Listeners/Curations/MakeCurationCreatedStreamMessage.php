<?php

namespace App\Listeners\Curations;

use Illuminate\Support\Facades\Bus;
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
     */
    public function handle(Created $event): void
    {
        Bus::dispatch(new CreatePrecurationStreamMessage($event->curation, 'created'));
    }
}
