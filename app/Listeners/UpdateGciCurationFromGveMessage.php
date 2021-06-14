<?php

namespace App\Listeners;

use App\Gci\GciMessage;
use Illuminate\Support\Facades\Bus;
use App\DataExchange\Events\Received;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\AppDataExchangeEventsReceived;
use App\Jobs\Gci\UpdateGciCurationFromStreamMessage;

class UpdateGciCurationFromGveMessage
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  App\DataExchange\Events\Received  $event
     * @return void
     */
    public function handle(Received $event)
    {
        Bus::dispatch(new UpdateGciCurationFromStreamMessage(new GciMessage($event->message->payload)));
    }
}
