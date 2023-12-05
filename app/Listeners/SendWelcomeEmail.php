<?php

namespace App\Listeners;

use App\Events\User\Created;
use App\Notifications\Users\Welcome;

class SendWelcomeEmail
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
        $event->user->notify(new Welcome());
    }
}
