<?php

namespace App\Listeners\Mail;

use App\Email;
use Symfony\Component\Mime\Address;
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
    public function handle(MessageSent $event): Email
    {
        // dump('text body: '.$event->message->getTextBody());
        // dd('html body' . $event->message->getHtmlBody());
        $record = Email::create([
            'from' => $this->addressesToHash($event->message->getFrom()),
            'sender' => $this->addressesToHash([$event->message->getSender()]),
            'reply_to' => $this->addressesToHash($event->message->getReplyTo()),
            'to' => $this->addressesToHash($event->message->getTo()),
            'cc' => $this->addressesToHash($event->message->getCc()),
            'bcc' => $this->addressesToHash($event->message->getBcc()),
            'subject' => $event->message->getSubject(),
            'body' => $event->message->getTextBody(),
        ]);

        return $record;
    }

    private function addressesToHash(Array|Address $addresses): array|null
    {
        $hash = [];
        $presentAddresses = array_filter($addresses);
        array_walk($presentAddresses, function ($address) use (&$hash) {
            $hash[$address->getAddress()] = $address->getName();
        });

        if (count($hash) == 0) {
            return null;
        }

        return $hash;
    }

}
