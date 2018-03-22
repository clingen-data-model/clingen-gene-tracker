<?php

namespace App\Listeners;

use App\Events\User\Created;
use App\Mail\UserWelcome;

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
        \Mail::to($event->user)->send(new UserWelcome());
    }
}
