<?php

namespace App\Listeners\Curations;

use App\Events\Curation\Saving;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curations\AugmentWithHgncInfo as HgncInfoJob;

class AugmentWithHgncInfo
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(Saving $event)
    {
        if (
            $event->curation->isDirty('gene_symbol')
            || is_null($event->curation->hgnc_id)
            || is_null($event->curation->hgnc_name)
        ) {
            try {
                HgncInfoJob::dispatchNow($event->curation);
            } catch (HttpNotFoundException $e) {
                \Log::warning($e->getMessage());
            }
        }
    }
}
