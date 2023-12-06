<?php

namespace App\Listeners\Curations;

use App\Events\Phenotypes\OmimRemovedPhenotype;

class UnlinkRemovedPhenotype
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
    public function handle(OmimRemovedPhenotype $event)
    {
        $event->phenotype->curations->each(function ($curation) use ($event) {
            $curation->phenotypes()->detach($event->phenotype);
        });
    }
}
