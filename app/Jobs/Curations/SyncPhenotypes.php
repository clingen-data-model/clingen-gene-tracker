<?php

namespace App\Jobs\Curations;

use App\Phenotype;
use App\Curation;
use App\Events\Curation\CurationPhenotypesUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Validation\ValidationException;

class SyncPhenotypes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $errors = [];

    public function __construct(private Curation $curation, private array $phenotypeIds)
    {
    }

    public function handle()
    {
        if (!$this->phenotypeIdsAreValid()) {
            throw ValidationException::withMessages($this->errors);
        }

        $previousIds = $this->curation->phenotypes->pluck('id');

        if ($previousIds->toArray() == $this->phenotypeIds) {
            return;
        }

        $this->curation->phenotypes()->sync($this->phenotypeIds);

        CurationPhenotypesUpdated::dispatch($this->curation->fresh(), $previousIds);
    }

    private function phenotypeIdsAreValid()
    {
        $uniq = collect($this->phenotypeIds)->unique();
        $matchingIds = Phenotype::whereIn('id', $uniq)
            ->select('id')
            ->get()
            ->pluck('id');

        if ($uniq->count() != $matchingIds->count()) {
            $this->errors['phenotype_ids'] = [
                "One or more of the ids for phenotypes you selected are not valid."
            ];
            return false;
        }

        return true;
    }
}
