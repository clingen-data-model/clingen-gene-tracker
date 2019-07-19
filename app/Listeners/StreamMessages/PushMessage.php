<?php

namespace App\Listeners\StreamMessages;

use App\Contracts\MessagePusher;
use App\Events\StreamMessages\Created;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exceptions\StreamingServiceException;
use Carbon\Carbon;

class PushMessage
{
    protected $pusher;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MessagePusher $pusher)
    {
        //
        $this->pusher = $pusher;
    }

    /**
     * Handle the event.
     *
     * @param  Created  $event
     * @return void
     */
    public function handle(Created $event)
    {
        try {
            $this->pusher->topic($event->streamMessage->topic);
            $this->pusher->push($event->streamMessage->message);

            $event->streamMessage->update([
                'sent_at' => Carbon::now()
            ]);
        } catch (StreamingServiceException $e) {
            report($e);
        }
    }
}
