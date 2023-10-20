<?php

namespace App\Listeners\Curations;

use Illuminate\Support\Facades\Bus;
use App\DataExchange\MessageFactories\MessageFactoryInterface;
use App\Events\Curation\Deleted;
use App\Jobs\Curations\CreatePrecurationStreamMessage;

class MakeCurationDeletedStreamMessage
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
     */
    public function handle(Deleted $event): void
    {
        Bus::dispatchSync(new CreatePrecurationStreamMessage($event->curation, 'deleted'));
    }
}
