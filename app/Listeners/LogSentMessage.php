<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogSentMessage
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
     * @param  MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        $messageInfo = [
            'to' => $event->message->getTo(),
            'from' => $event->message->getFrom(),
            'subject' => $event->message->getSubject(),
            'body' => $event->message->getBody(),
        ];
        
        \Log::info('Email sent', $messageInfo);
    }
}
