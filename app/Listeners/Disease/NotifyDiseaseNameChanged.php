<?php

namespace App\Listeners\Disease;

use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\Disease\DiseaseNameChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Disease\NameChangedNotification;

class NotifyDiseaseNameChanged
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
     * @param  DiseaseNameChanged  $event
     * @return void
     */
    public function handle(DiseaseNameChanged $event)
    {
        $event->disease->load('curations', 'curations.expertPanel.coordinators');

        if ($event->disease->is_obsolete) {
            return;
        }

        $event->disease
            ->curations
            ->each(function ($curation) use ($event) {
                Bus::dispatch(
                    new NotifyCoordinatorsAboutCuration(
                        $curation, 
                        NameChangedNotification::class, 
                        ['oldName' => $event->oldName]
                    )
                );
            });
    }
}
