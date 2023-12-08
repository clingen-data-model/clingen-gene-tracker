<?php

namespace App\Jobs\Curations;

use App\Classification;
use App\Curation;
use Carbon\Carbon;
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
        $this->date = Carbon::parse($date);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->isCurrentClassification() || $this->isExistingDatedClassification()) {
            return;
        }

        $this->curation->classifications()->attach([
            $this->classification->id => [
                'classification_date' => $this->date,
            ],
        ]);
    }

    private function isCurrentClassification()
    {
        return $this->curation->currentClassification->id == $this->classification->id;
    }

    private function isExistingDatedClassification()
    {
        $filtered = $this->curation->classifications
            ->filter(function ($classification) {
                return $classification->id == $this->classification->id
                    && $classification->pivot->classification_date = $this->date;
            });

        return $filtered->count() > 0;
    }
}
