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
        $phenotypes = Phenotype::whereIn('mim_number', $this->phenotypes->pluck('mim_number'))->get();

        $newMims = $this->phenotypes
                        ->pluck('mim_number')
                        ->diff($phenotypes->pluck('mim_number'))
                        ->unique(); // get unique for the case when two records share the same mim number

        $newMims->each(function ($mimNumber) use ($phenotypes) {
            $data = [
                'mim_number' => $mimNumber,
                'name' => $this->phenotypes->firstWhere('mim_number', $mimNumber)['name']
            ];

            $phenotypes->push(Phenotype::create($data));
        });
        
        $this->curation->phenotypes()->sync($phenotypes->pluck('id'));
    }
}
