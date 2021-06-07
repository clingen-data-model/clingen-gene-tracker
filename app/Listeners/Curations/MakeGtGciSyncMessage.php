<?php

namespace App\Listeners\Curations;

use App\Curation;
use App\Events\Curation\CurationEvent;
use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Curations\CreateStreamMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class MakeGtGciSyncMessage
{
    private $topic;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->topic = config('dx.topics.outgoing.gt-gci-sync');
    }

    /**
     * Handle the event.
     *
     * @param  Updated  $event
     * @return void
     */
    public function handle(CurationEvent $event)
    {
        if (!$this->hasGeneDiseaseMoi($event->curation)) {
            return;
        }
        
        if (!$this->precurationCompleted($event->curation)) {
            return;
        }

        if (
            $this->statusWasChanged($event->curation)
            || $this->moiAdded($event->curation)
            || $this->diseaseAdded($event->curation)
        ) {
            Bus::dispatch(new CreateStreamMessage($this->topic, $event->curation, 'precuration-completed'));
            return;
        }
    }

    private function hasGeneDiseaseMoi(Curation $curation)
    {
        if (!$curation->hgnc_id) {
            return false;
        }

        if (!$curation->mondo_id) {
            return false;
        }

        if (!$curation->moi_id) {
            return false;
        }

        return true;
    }

    private function precurationCompleted(Curation $curation)
    {
        return $curation->curation_status_id == config('curations.statuses.precuration-complete');
    }

    private function statusWasChanged(Curation $curation)
    {
        return $curation->isDirty('curation_status_id');
    }

    private function moiAdded(Curation $curation)
    {
        return $curation->isDirty('moi_id')
                && $curation->getOriginal('moi_Id') == null;
    }

    private function diseaseAdded(Curation $curation)
    {
        return $curation->isDirty('mondo_id')
                && $curation->getOriginal('mondo_id') == null;
    }
}
