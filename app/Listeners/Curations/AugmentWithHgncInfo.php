<?php

namespace App\Listeners\Curations;

use App\Event\Curation\Saving;
use Illuminate\Queue\InteractsWithQueue;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curations\AugmentWithHgncInfo as HgncInfoJob;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        if ($event->curation->isDirty('gene_symbol')) {
            try {
                HgncInfoJob::dispatch($event->curation);
            } catch (HttpNotFoundException $e) {
                \Log::warning($e->getMessage());
            }
        }
    }
}
