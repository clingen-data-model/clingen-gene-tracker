<?php

namespace App\Listeners\Curations;

use App\StreamMessage;
use App\Events\Curation\Deleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\Curations\CreatePrecurationStreamMessage;
use App\DataExchange\MessageFactories\MessageFactoryInterface;

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
     *
     * @param  Deleted  $event
     * @return void
     */
    public function handle(Deleted $event)
    {
        \Bus::dispatchNow(new CreatePrecurationStreamMessage($event->curation, 'deleted'));
    }
}
