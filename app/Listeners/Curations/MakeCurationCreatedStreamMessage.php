<?php

namespace App\Listeners\Curations;

use App\DataExchange\MessageFactories\MessageFactoryInterface;
use App\Events\Curation\Created;
use App\Jobs\Curations\CreatePrecurationStreamMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\StreamMessage;

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
     * @param  Created  $event
     * @return void
     */
    public function handle(Created $event)
    {
        \Bus::dispatch(new CreatePrecurationStreamMessage($event->curation, 'created'));
    }
}
