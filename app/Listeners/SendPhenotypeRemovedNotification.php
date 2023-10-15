<?php

namespace App\Listeners;

use App\Events\Phenotypes\OmimRemovedPhenotype;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\PhenotypeOmimEntryRemoved;
use Illuminate\Support\Facades\Bus;

class SendPhenotypeRemovedNotification
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
    public function handle(OmimRemovedPhenotype $event): void
    {
        $event->phenotype->curations->each(function ($curation) use ($event) {
            $notification = new NotifyCoordinatorsAboutCuration($curation, PhenotypeOmimEntryRemoved::class, $event->phenotype);
            Bus::dispatch($notification);
        });
    }
}
