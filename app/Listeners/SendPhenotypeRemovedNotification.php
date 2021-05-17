<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Events\Phenotypes\OmimRemovedPhenotype;
use App\Notifications\Curations\PhenotypeOmimEntryRemoved;

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
     * @param  OmimRemovedPhenotype  $event
     * @return void
     */
    public function handle(OmimRemovedPhenotype $event)
    {
        $event->phenotype->curations->each(function ($curation) use ($event) {
            $notification = new NotifyCoordinatorsAboutCuration($curation, PhenotypeOmimEntryRemoved::class, $event->phenotype);
            Bus::dispatch($notification);
        });
    }
}
