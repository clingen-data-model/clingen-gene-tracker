<?php

namespace App\Listeners;

use App\Events\Phenotypes\OmimMovedPhenotype;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\PhenotypeOmimEntryMoved;
use Illuminate\Support\Facades\Log;

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
     */
    public function handle(OmimMovedPhenotype $event): void
    {
        Log::debug('entry moved: '.$event->phenotype->name.' to '.$event->phenotype->mim_number);
        $event->phenotype
            ->curations
            ->each(function ($curation) use ($event) {
                NotifyCoordinatorsAboutCuration::dispatch(
                    $curation,
                    PhenotypeOmimEntryMoved::class,
                    $event->phenotype->movedToPhenotypes,
                    $event->phenotype->name,
                    $event->phenotype->mim_number
                );
            });
    }
}
