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

    public function __construct(private Curation $curation, private $phenotypeIds)
    {
    }

    public function handle()
    {
        $phenotypes = Phenotype::whereIn('id', $this->phenotypeIds ?? []);
        if (!$phenotypes && $phenotypes->count() > 0) {
            return;
        }

        $this->curation->phenotypes()->sync($phenotypes->pluck('id'));
    }
}
