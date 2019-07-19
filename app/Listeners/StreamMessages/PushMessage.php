<?php

namespace App\Listeners\StreamMessages;

use Carbon\Carbon;
use App\Contracts\MessagePusher;
use App\Events\StreamMessages\Created;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\StreamingService\PushMessage as PushMessageJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exceptions\StreamingServiceException;

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
     *
     * @param  Created  $event
     * @return void
     */
    public function handle(Created $event)
    {
        PushMessageJob::dispatch($event->streamMessage);
    }
}
