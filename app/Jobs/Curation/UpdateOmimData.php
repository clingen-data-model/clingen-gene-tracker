<?php

namespace App\Jobs\Curation;

use App\Phenotype;
use App\Contracts\OmimClient;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\SendCurationMailToCoordinators;
use App\Mail\Curations\PhenotypeNomenclatureUpdated;

class UpdateOmimData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phenotype;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Phenotype $phenotype)
    {
        //
        $this->phenotype = $phenotype;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(OmimClient $omimClient)
    {
        $omimEntry = $omimClient->getEntry($this->phenotype->mim_number)[0]->entry;
        if ($this->nameUpdated($omimEntry)) {
            $oldName = $this->phenotype->name;
            $this->updatePhenotypeName($omimEntry);
            $this->sendNotification($oldName);
        }
    }

    private function nameUpdated($omimEntry)
    {
        return strtoupper($this->phenotype->name) != strtoupper($omimEntry->titles->preferredTitle);
    }

    private function updatePhenotypeName($omimEntry)
    {
        $this->phenotype->update([
            'name' => $omimEntry->titles->preferredTitle,
            'omim_entry' => $omimEntry
        ]);
    }

    private function sendNotification($oldName)
    {
        $this->phenotype
            ->curations
            ->each(function ($curation) use ($oldName){
                SendCurationMailToCoordinators::dispatch($curation, PhenotypeNomenclatureUpdated::class, $this->phenotype, $oldName);
            });
    }
}
