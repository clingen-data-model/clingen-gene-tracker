<?php

namespace App\Listeners;

use App\Events\Phenotypes\PhenotypeNameChanged;

class SendPhenotypeNameChangedNotification
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
     * @return void
     */
    public function handle(PhenotypeNameChanged $event)
    {
        //
    }
}
