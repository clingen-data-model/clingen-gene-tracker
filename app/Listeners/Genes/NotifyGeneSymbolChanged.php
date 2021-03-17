<?php

namespace App\Listeners\Genes;

use App\Curation;
use Illuminate\Support\Facades\Bus;
use App\Events\Genes\GeneSymbolChanged;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\GeneSymbolUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyGeneSymbolChanged
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
     * @param  GeneSymbolChanged  $event
     * @return void
     */
    public function handle(GeneSymbolChanged $event)
    {
        $curations = Curation::where('hgnc_id', $event->gene->hgnc_id)->get();
        $curations->each(function ($curation) use ($event) {
            $job = new NotifyCoordinatorsAboutCuration(
                $curation,
                GeneSymbolUpdated::class,
                $event->previousSymbol
            );
            Bus::dispatch($job);
        });
    }
}
