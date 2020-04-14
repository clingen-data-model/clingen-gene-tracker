<?php

namespace App\Listeners\SteamMessages;

use App\Jobs\SetState;
use App\Events\StreamMessages\Received;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\StreamingService\UpdateTopicPartitionOffset;

class SetTopicOffset
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Received  $event
     * @return void
     */
    public function handle(Received $event)
    {
        UpdateTopicPartitionOffset::dispatch($event->message->topic_name, $event->message->partition, $event->message->offset);
    }
}
