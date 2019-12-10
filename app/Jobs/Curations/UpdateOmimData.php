<?php

namespace App\Jobs\Curations;

use App\Phenotype;
use App\Contracts\OmimClient;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\SendCurationMailToCoordinators;
use App\Mail\Curations\PhenotypeNomenclatureUpdated;
use App\Mail\Curations\PhenotypeOmimEntryMoved;

class UpdateOmimData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phenotype;
    private $omimClient;

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
        $this->omimClient = $omimClient;
        $omimEntry = $omimClient->getEntry($this->phenotype->mim_number)[0]->entry;

        if ($this->nameUpdated($omimEntry)) {
            $oldName = $this->phenotype->name;
            
            if ($this->entryHasMoved($omimEntry)) {
                $oldMimNumber = $this->phenotype->mim_number;
                $newOmimEntry = $this->omimClient->getEntry($omimEntry->movedTo)[0]->entry;
                $this->updatePhenotypeWithNewEntry($newOmimEntry);
                $this->sendEntryMovedNotification($oldName, $oldMimNumber);
                return;
            }
            
            $this->updatePhenotypeName($omimEntry);
            $this->sendNameChangeNotification($oldName);
        }
    }
    private function nameUpdated($omimEntry)
    {
        return strtoupper($this->phenotype->name) != strtoupper($omimEntry->titles->preferredTitle);
    }

    private function entryHasMoved($omimEntry)
    {
        return $omimEntry->status == 'moved' && !is_null($omimEntry->movedTo);
    }

    private function updatePhenotypeName($omimEntry)
    {
        $this->phenotype->update([
            'name' => $omimEntry->titles->preferredTitle,
            'omim_entry' => $omimEntry
        ]);
    }

    private function updatePhenotypeWithNewEntry($newEntry)
    {
        $existingPhenotype = Phenotype::findByMimNumber($newEntry->mimNumber);
        if ($existingPhenotype) {
            $this->phenotype->curations->each(function ($curation) use ($existingPhenotype) {
                $curation->phenotypes()->syncWithoutDetaching($existingPhenotype->id);
                $curation->phenotypes()->detach($this->phenotype->id);
            });
            $this->phenotype = $existingPhenotype;
            return;
        }

        $this->phenotype->update([
            'mim_number' => $newEntry->mimNumber,
            'name' => $newEntry->titles->preferredTitle,
            'omim_entry' => $newEntry
        ]);
    }

    private function phenotypeWithMimExists($mimNumber)
    {
        return !is_null(Phenotype::findByMimNumber($mimNumber));
    }

    private function sendNameChangeNotification($oldName)
    {
        $this->phenotype
            ->curations
            ->each(function ($curation) use ($oldName) {
                SendCurationMailToCoordinators::dispatch($curation, PhenotypeNomenclatureUpdated::class, $this->phenotype, $oldName);
            });
    }

    private function sendEntryMovedNotification($oldName, $oldMimNumber)
    {
        $this->phenotype
            ->curations
            ->each(function ($curation) use ($oldName, $oldMimNumber) {
                SendCurationMailToCoordinators::dispatch($curation, PhenotypeOmimEntryMoved::class, $this->phenotype, $oldName, $oldMimNumber);
            });
    }
}
