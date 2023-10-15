<?php

namespace App\Listeners\Disease;

use App\Events\Disease\MondoTermObsoleted;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Disease\MondoTermObsoleteNotification;
use Illuminate\Support\Facades\Bus;

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
     */
    public function handle(MondoTermObsoleted $event): void
    {
        \Log::debug(__METHOD__);
        $event->disease->load('curations', 'curations.expertPanel.coordinators');

        $event->disease
            ->curations
            ->each(function ($curation) {
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
