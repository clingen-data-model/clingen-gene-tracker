<?php

namespace App\Listeners;

use App\Events\Phenotypes\PhenotypeNameChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
     * @param  PhenotypeNameChanged  $event
     * @return void
     */
    public function handle(PhenotypeNameChanged $event)
    {
        //
    }
}
