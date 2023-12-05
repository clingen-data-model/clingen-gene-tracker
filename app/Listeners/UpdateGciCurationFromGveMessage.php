<?php

namespace App\Listeners;

use App\DataExchange\Events\Received;
use App\Gci\GciMessage;
use App\Jobs\Gci\UpdateGciCurationFromStreamMessage;
use Illuminate\Support\Facades\Bus;

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
     */
    public function handle(Received $event): void
    {
        Bus::dispatch(new UpdateGciCurationFromStreamMessage(new GciMessage($event->message->payload)));
    }
}
