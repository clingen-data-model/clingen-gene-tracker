<?php

namespace App\Listeners\Curations;

use App\Events\Curation\Saved;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curations\AugmentWithMondoInfo as MondoJob;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AugmentWithMondoInfo
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
     * @param  Saved  $event
     * @return void
     */
    public function handle(Saved $event)
    {
        if ($event->curation->isDirty('mondo_id')) {
            try {
                MondoJob::dispatch($event->curation);
            } catch (HttpNotFoundException $e) {
                \Log::warning($e->getMessage());
            }
        }
    }
}
