<?php

namespace App\Listeners;

use App\User;
use App\Activity;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\ExpertPanel\Events\ExpertPanelEvent;

class RecordEvent
{
    private $logger;

    /**
     * Handle the event.
     *
     * @param  RecordableEvent  $event
     * @return void
     */
    public function handle(RecordableEvent $event)
    {
        $this->logger = activity($event->getLog());

        $this->addCauser();
        $this->addSubject($event);
        $this->addEventAttribute($event);
        $this->addEventUuid($event);
        $this->addProperties($event);
        $this->logger->createdAt($event->getLogDate());
        $this->logger->log($event->getLogEntry());
    }

    private function addSubject($event): void
    {
        if ($event->hasSubject()) {
            $this->logger->performedOn($event->getSubject());
        }
    }

    private function addEventAttribute($event): void
    {
        $this->logger->event($event->getEventType());
    }

    private function addEventUuid($event): void
    {
        $this->logger->tap(function (Activity $activity) use ($event) {
            $activity->event_uuid = $event->getEventUuid();
        });
    }

    private function addProperties($event): void
    {
        $this->logger->withProperties($event->getProperties());
    }



    private function addCauser()
    {
        $causer = User::find(Auth::id());
        if ($causer) {
            $this->logger->causedBy($causer);
        }
    }
}
