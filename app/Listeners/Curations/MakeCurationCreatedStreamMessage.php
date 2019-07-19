<?php

namespace App\Listeners\Curations;

use App\Events\Curation\Created;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\StreamMessage;

class MakeCurationCreatedStreamMessage
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
     * @param  Created  $event
     * @return void
     */
    public function handle(Created $event)
    {
        StreamMessage::create([
            'topic' => config('streaming-service.topic'),
            'message' => json_encode([
                'event' => 'created',
                'curation' => $event->curation->loadForMessage()->toArray()
            ])
        ]);
    }
}
