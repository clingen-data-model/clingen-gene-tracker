<?php

namespace App\Listeners\Disease;

use App\Events\Disease\DiseaseNameChanged;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Disease\NameChangedNotification;
use Illuminate\Support\Facades\Bus;

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
     * @return void
     */
    public function handle(DiseaseNameChanged $event): void
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
