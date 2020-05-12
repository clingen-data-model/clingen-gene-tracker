<?php

namespace App\Listeners\Mail;

use App\Email;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreMailInDatabase
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
        $email = Email::create([
            'from' => $event->message->getFrom(),
            'sender' => $event->message->getSender(),
            'reply_to' => $event->message->getReplyTo(),
            'to' => $event->message->getTo(),
            'cc' => $event->message->getCc(),
            'bcc' => $event->message->getBcc(),
            'subject' => $event->message->getSubject(),
            'body' => $event->message->getBody(),
        ]);
    }
}
