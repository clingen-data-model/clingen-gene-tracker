<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\Phenotypes\OmimMovedPhenotype;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\PhenotypeOmimEntryMoved;

class SendPhenotypeMovedNotification
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
        \Log::debug('entry moved: '.$event->phenotype->name.' to '.$event->phenotype->mim_number);
        $event->phenotype
            ->curations
            ->each(function ($curation) use ($event) {
                $movedToPheno = $event->phenotype->movedToPhenotype;
                NotifyCoordinatorsAboutCuration::dispatch(
                    $curation,
                    PhenotypeOmimEntryMoved::class,
                    $movedToPheno,
                    $event->phenotype->name,
                    $event->phenotype->mim_number
                );
            });
    }
}
