<?php

namespace App\Listeners\Curations;

use App\Curation;
use App\Events\Curation\CurationEvent;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Curations\CreateStreamMessage;

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
        $curation = $event->curation;

        // Archive / unarchive should be treated as a separate GT -> GCI case.
        if ($this->archiveChanged($curation)) {
            // We still want some way for GCI to identify the record:
            // either already linked by gdm_uuid, or enough core data to match it.
            if (!$this->linkedToGciRecord($curation) && !$this->hasGeneDiseaseMoi($curation)) {
                return;
            }
            $eventType = $curation->archived_at ? 'curation_archived' : 'curation_unarchived';
            Bus::dispatch(new CreateStreamMessage($this->topic, $curation, $eventType));
            return;
        }

        // Existing GT -> GCI sync logic for normal precuration updates
        if ($this->linkedToGciRecord($curation)) {
            return;
        }

        if (!$this->hasGeneDiseaseMoi($curation)) {
            // \Log::debug('  does not yet have gene, disease, or moi: ', $curation->only('gene_symbol', 'moi_id', 'mondo_id'));
            return;
        }

        if (!$this->precurationCompleted($curation)) {
            return;
        }
        // \Log::info(' has precuration-complete status');

        if (
            $this->statusWasChanged($curation)
            || $this->moiChanged($curation)
            || $this->diseaseChanged($curation)
        ) {
            // Log::debug('  meets criteria');
            $eventType = 'precuration_completed';
            // \Log::debug('  $this->moiUpdated($curation)', [$this->moiUpdated($curation)]);
            if ($this->moiUpdated($curation) || $this->diseaseUpdated($curation)) {
                // \Log::debug('  moi or mondo updated.');
                $eventType = 'gdm_updated';
            }
            Bus::dispatch(new CreateStreamMessage($this->topic, $curation, $eventType));
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
        // \Log::debug('  $curation->curation_status_id: '.$curation->curation_status_id);
        return $curation->curation_status_id == config('curations.statuses.precuration-complete');
    }

    private function statusWasChanged(Curation $curation)
    {
        return $curation->isDirty('curation_status_id');
    }

    private function moiChanged(Curation $curation)
    {
        // \Log::debug('  $curation->isDirty(moi_id)', [$curation->isDirty('moi_id')]);
        return $curation->isDirty('moi_id');
    }

    private function diseaseChanged(Curation $curation)
    {
        return $curation->isDirty('mondo_id');
    }

    private function moiUpdated(Curation $curation)
    {
        // \Log::debug('  moiUpdated?: ', ['new' => $curation->moi_id, 'original' => $curation->getOriginal('moi_id')]);
        if (is_null($curation->getOriginal('moi_id'))) {
            return false;
        }

        if ($curation->getOriginal('moi_id') == $curation->moi_id) {
            return false;
        }
        
        return true;
    }
    
    private function diseaseUpdated(Curation $curation)
    {
        // \Log::debug('  new mondo_id, ', ['new' => $curation->mondo_id, 'original' => $curation->getOriginal('mondo_id')]);
        if (is_null($curation->getOriginal('mondo_id'))) {
            return false;
        }
        if ($curation->getOriginal('mondo_id') == $curation->mondo_id) {
            return false;
        }

        return true;
    }
    private function archiveChanged(Curation $curation)
    {
        return $curation->wasChanged('archived_at');
    }
}
