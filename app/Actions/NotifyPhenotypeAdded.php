<?php

namespace App\Actions;

use App\Curation;
use App\Phenotype;
use Illuminate\Support\Facades\Bus;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Events\Phenotypes\PhenotypeAddedForGene;
use App\Notifications\Curations\PhenotypeAddedForCurationNotification;

class NotifyPhenotypeAdded
{
    use AsListener;

    public function handle(Curation $curation, Phenotype $phenotype): void
    {
        Bus::dispatch(new NotifyCoordinatorsAboutCuration($curation, PhenotypeAddedForCurationNotification::class, $phenotype));
    }

    public function asListener(PhenotypeAddedForGene $event): void
    {
        $curations = Curation::where('hgnc_id', $event->gene->hgnc_id)->get();
        $curations->each(function ($curation) use ($event) {
            $this->handle($curation, $event->phenotype);
        });
    }
    
    
}
