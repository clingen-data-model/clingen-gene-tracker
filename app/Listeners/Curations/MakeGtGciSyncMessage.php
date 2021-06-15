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
        // \Log::debug(__METHOD__);
        if ($this->linkedToGciRecord($event->curation)) {
            // \Log::debug('alread linked to gci record');
            return;
        }

        if (!$this->hasGeneDiseaseMoi($event->curation)) {
            // \Log::debug('does not yet have gene, disease, or moi');
            return;
        }
        
        if (!$this->precurationCompleted($event->curation)) {
            // \Log::debug('status not correct');
            return;
        }

        if (
            $this->statusWasChanged($event->curation)
            || $this->moiChanged($event->curation)
            || $this->diseaseChanged($event->curation)
        ) {
            // \Log::debug('meets criteria');
            $eventType = 'precuration-completed';
            if ($this->moiUpdated($event->curation) || $this->diseaseUpdated($event->curation)) {
                \Log::debug('moi or mondo updated.');
                $eventType = 'gdm-updated';
            }
            Bus::dispatch(new CreateStreamMessage($this->topic, $event->curation, $eventType));
            return;
        }
    }

    private function linkedToGciRecord(Curation $curation)
    {
        return !is_null($curation->gdm_uuid);
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
        \Log::debug("status changed");
        return $curation->isDirty('curation_status_id');
    }

    private function moiChanged(Curation $curation)
    {
        \Log::debug("moi changed");
        return $curation->isDirty('moi');
    }

    private function diseaseChanged(Curation $curation)
    {
        \Log::debug("disease changed");
        return $curation->isDirty('mondo_id');
    }

    private function moiUpdated(Curation $curation)
    {
        return ($curation->getOriginal('moi_id') !== $curation->moi_id);
    }
    
    private function diseaseUpdated(Curation $curation)
    {
        return ($curation->getOriginal('mondo_id') !== $curation->mondo_id);
    }
}
