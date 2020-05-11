<?php

namespace App\Listeners;

use App\Events\User\Created;
use App\Notifications\users\Welcome;

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
     *
     * @param  Created  $event
     * @return void
     */
    public function handle(Created $event)
    {
        $event->user->notify(new Welcome());
    }
}
