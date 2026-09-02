<?php

namespace App\Jobs\Curations;

use App\Actions\Curations\RecordCurationFieldEvent;
use App\Classification;
use App\Curation;
use App\Curations\CurationField;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Records a classification observation. See AddStatus.
 */
class AddClassification
{
    use Dispatchable, Queueable;

    public $curation;
    public $classification;
    public $date;
    public $source;
    public $sourceEventKey;

    public function __construct(
        Curation $curation,
        Classification $classification,
        ?string $date = null,
        string $source = 'ui',
        ?string $sourceEventKey = null
    ) {
        $this->curation = $curation;
        $this->classification = $classification;
        $this->date = $date ? Carbon::parse($date) : now();
        $this->source = $source;
        $this->sourceEventKey = $sourceEventKey ?? $this->defaultSourceEventKey();
    }

    public function handle()
    {
        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Classification,
            $this->classification->id,
            $this->date,
            $this->source,
            $this->sourceEventKey
        );
    }

    private function defaultSourceEventKey(): string
    {
        return 'ui:classification:'.$this->date->toDateString().':'.$this->classification->id;
    }
}
