<?php

namespace App\Listeners\Curations;

use App\StreamMessage;
use App\Events\Curation\Deleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MakeCurationDeletedStreamMessage
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
     * @param  Deleted  $event
     * @return void
     */
    public function handle(Deleted $event)
    {
        StreamMessage::create([
            'topic' => config('streaming-service.gci-topic'),
            'message' => json_encode([
                'event' => 'deleted',
                'curation' => ['id' => $event->curation->id]
            ])
        ]);
    }
}
