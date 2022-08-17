<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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

        $included = $this->prepareIncludedPhenotypes();
        $excluded = $this->getExcludedPhenotypes();
        $phenotypes = $included->union($excluded);

        $this->curation->phenotypes()->sync($phenotypes);
    }

    private function prepareIncludedPhenotypes(): Collection
    {
        return $this->phenotypes->map(function ($pheno) {
                if (is_object($pheno) && get_class($pheno) == Phenotype::class) {
                    return $pheno;
                } 
                return Phenotype::firstOrCreate($pheno);
            })
            ->keyBy('id')
            ->mapWithKeys(fn($ph, $key) => [$ph->id => ['selected' => 1]]);
        }

    private function getExcludedPhenotypes()
    {
        $includedMims = $this->phenotypes->pluck('mim_Number');
        return $this->curation->gene->phenotypes
                ->filter(fn($p) => !$includedMims->contains($p->mim_number))
                ->sortBy('mim_number')
                ->keyBy('id')
                ->mapWithKeys(fn($ph, $key) => [$ph->id => ['selected' => 0]]);
    }
    
    
}
