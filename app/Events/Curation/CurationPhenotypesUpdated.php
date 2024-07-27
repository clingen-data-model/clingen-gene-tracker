<?php
namespace App\Events\Curation;

use App\Curation;
use App\Events\RecordableEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class CurationPhenotypesUpdated extends RecordableEvent
{
    public function __construct(public Curation $curation, public $previousPhenotypeIds)
    {
    }

    public function getLog(): string {
        return 'curations';
    }

    public function hasSubject(): bool {
        return true;
    }

    public function getSubject(): Model
    {
        return $this->curation;
    }

    public function getProperties(): array 
    {
        $currentPhenotypeIds = $this->curation->phenotypes->pluck('id')->toArray();
        return [
            'old' => $this->previousPhenotypeIds,
            'new' => $currentPhenotypeIds
        ];
    }

    public function getLogEntry(): string
    {
        return 'Phenotypes updated for curation '.$this->curation->id;
    }

    public function getLogDate():Carbon {
        return Carbon::now();
    }
}

