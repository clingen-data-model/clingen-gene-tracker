<?php

namespace App\Jobs\Curations;

use App\Phenotype;
use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPhenotypes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $curation;
    protected $phenotypes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, $phenotypes)
    {
        $this->curation = $curation;
        $this->phenotypes = collect($phenotypes);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->phenotypes && $this->phenotypes->count() > 0) {
            return;
        }

        $curationPhenos = $this->phenotypes->map(function ($pheno) {
            return Phenotype::firstOrCreate($pheno);
        });
        $this->curation->phenotypes()->sync($curationPhenos->pluck('id'));
    }
}
