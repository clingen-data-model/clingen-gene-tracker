<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use App\Classification;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class AddClassification
{
    use Dispatchable, Queueable;

    public $curation;

    public $classification;

    public $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, Classification $classification, string $date = null)
    {
        $this->curation = $curation;
        $this->classification = $classification;
        $this->date = $date ? Carbon::parse($date) : now();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isExistingDatedClassification()) {
            return;
        }

        if ($this->isCurrentClassification() && $this->isExistingSameDayClassification()) {
            return;
        }

        $this->curation->classifications()->attach([
            $this->classification->id => [
                'classification_date' => $this->date
            ],
        ]);
    }

    private function isCurrentClassification()
    {
        return $this->curation->classificationBefore($this->date)->id == $this->classification->id;
    }

    private function isExistingDatedClassification()
    {
        return $this->curation->classifications()
            ->where('classifications.id', $this->classification->id)
            ->wherePivot('classification_date', $this->date->format('Y-m-d H:i:s'))
            ->exists();
    }

    private function isExistingSameDayClassification()
    {
        return $this->curation->classifications()
            ->where('classifications.id', $this->classification->id)
            ->whereDate('classification_curation.classification_date', $this->date->toDateString())
            ->exists();
    }
}