<?php

namespace App\Jobs;

use App\Gene;
use App\Phenotype;
use App\Contracts\OmimClient;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportOmimPhenotype
{
    use Dispatchable;

    protected $mimNumber;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($mimNumber)
    {
        //
        $this->mimNumber = $mimNumber;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(OmimClient $omim)
    {
        $omimEntry = $omim->getEntry($omim);
        $phenotype = Phenotype::firstOrCreate(
            ['mim_number' => $omimEntry->mimNumber],
            [
                'name' => $omimEntry->phenotypeName,
                'status' => $omimEntry->status,
                'moi' => $omimEntry->moi
            ]
        );
        
        if ($omimEntry->mappedGeneMimNumber) {
            $gene = Gene::findByOmimId($omimEntry->mappedGeneMimNumber);
            if ($gene) {
                $phenotype->genes()->attach($gene);
            }
        }
    }
}
