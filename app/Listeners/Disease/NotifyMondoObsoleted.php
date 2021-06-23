<?php

namespace App\Listeners\Disease;

use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\Disease\MondoTermObsoleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Disease\MondoTermObsoleteNotification;

class NotifyMondoObsoleted
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        \Log::debug(__METHOD__);
    }

    /**
     * Handle the event.
     *
     * @param  MondoTermObsoleted  $event
     * @return void
     */
    public function handle(MondoTermObsoleted $event)
    {
        \Log::debug(__METHOD__);
        $event->disease->load('curations', 'curations.expertPanel.coordinators');
        
        $event->disease
        ->curations
        ->each(function ($curation) use ($event) {
                \Log::debug('notify for curation '.$curation->id);
                Bus::dispatch(
                    new NotifyCoordinatorsAboutCuration(
                        $curation,
                        MondoTermObsoleteNotification::class
                    )
                );
            });
    }
}
