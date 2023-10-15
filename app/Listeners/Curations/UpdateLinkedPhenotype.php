<?php

namespace App\Listeners\Curations;

use App\Events\Phenotypes\OmimMovedPhenotype;

class UpdateLinkedPhenotype
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
     */
    public function handle(OmimMovedPhenotype $event): void
    {
        $phenotype = $event->phenotype;
        $phenotype->curations->each(function ($curation) use ($phenotype) {
            $curation->phenotypes()->detach($phenotype);
            $movedToPhenotypes = $phenotype->fresh()->movedToPhenotypes->pluck('id');
            $curation->phenotypes()->attach($movedToPhenotypes);
        });
    }
}
