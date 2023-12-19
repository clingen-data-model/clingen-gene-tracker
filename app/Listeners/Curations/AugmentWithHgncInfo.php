<?php

namespace App\Listeners\Curations;

use App\Events\Curation\Saving;
use Illuminate\Queue\InteractsWithQueue;
use App\Exceptions\HttpNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
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
     * @param  Saving  $event
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
                HgncInfoJob::dispatchSync($event->curation);
            } catch (HttpNotFoundException $e) {
                \Log::warning($e->getMessage());
            }
        }
    }
}
