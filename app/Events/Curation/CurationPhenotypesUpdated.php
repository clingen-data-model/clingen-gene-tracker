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
        return 'phenotypes';
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
        return [
            'old' => $this->previousPhenotypeIds,
            'new' => $this->curation->phenotypes->pluck('ids')
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

