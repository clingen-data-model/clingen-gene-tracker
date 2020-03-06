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
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->curation->currentClassification->id != $this->classification->id) {
            $this->curation->classifications()->attach([
                $this->classification->id => [
                    'classification_date' => $this->date ?? Carbon::now()
                ]
            ]);
        }
    }
}
