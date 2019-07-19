<?php

namespace App\Listeners\Curations;

use App\StreamMessage;
use App\Events\Curation\Updated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MakeCurationUpdatedStreamMessage
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
     * @param  Updated  $event
     * @return void
     */
    public function handle(Updated $event)
    {
        StreamMessage::create([
            'topic' => config('streaming-service.topic'),
            'message' => json_encode([
                'event' => 'updated',
                'curation' => $event->curation->loadForMessage()->toArray()
            ])
        ]);
    }
}
