<?php

namespace App\Listeners\StreamMessages;

use App\DataExchange\Events\Created;
use App\DataExchange\Jobs\PushMessage as PushMessageJob;

class PushMessage
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
    public function handle(Created $event): void
    {
        PushMessageJob::dispatch($event->streamMessage);
    }
}
