<?php

namespace App\Listeners;

use App\Events\User\Created;
use App\Notifications\Users\Welcome;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SendWelcomeEmail implements ShouldHandleEventsAfterCommit
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
     * @return void
     */
    public function handle(Created $event)
    {
        $event->user->notify(new Welcome());
    }
}
