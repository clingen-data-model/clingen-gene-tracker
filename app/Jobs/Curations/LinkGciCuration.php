<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\GciCuration;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\SerializesModels;
use App\Jobs\ReplayGciEventsForCuration;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * @group gci
 * @group curations
 */
class LinkGciCuration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        //
        $this->curation = $curation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $searchData = [
            'hgnc_id' => $this->curation->hgnc_id,
            'mondo_id' => $this->curation->mondo_id,
            'moi_id' => $this->curation->moi_id,
        ];
        if ($this->curation->expertPanel->affilation) {
            $searchData['affiliation_id'] = $this->curation->expertPanel->affiliation->parent_id;
        }

        $gciCuration = GciCuration::where($searchData)->first();

        if (!$gciCuration) {
            return;
        }

        $this->curation->update(['gdm_uuid' => $gciCuration->gdm_uuid]);
        Bus::dispatch(new ReplayGciEventsForCuration($this->curation));
    }
}
