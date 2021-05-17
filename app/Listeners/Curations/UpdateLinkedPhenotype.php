<?php

namespace App\Listeners\Curations;

use App\Events\Phenotypes\OmimMovedPhenotype;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
     *
     * @param  OmimMovedPhenotype  $event
     * @return void
     */
    public function handle(OmimMovedPhenotype $event)
    {
        $phenotype = $event->phenotype;
        $phenotype->curations->each(function ($curation) use ($phenotype) {
            $curation->phenotypes()->detach($phenotype);
            $movedToPhenotypes = $phenotype->fresh()->movedToPhenotypes->pluck('id');
            $curation->phenotypes()->attach($movedToPhenotypes);
        });
    }
}
