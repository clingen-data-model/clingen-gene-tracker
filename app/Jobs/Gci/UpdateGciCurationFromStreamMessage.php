<?php

namespace App\Jobs\Gci;

use App\Affiliation;
use App\GciCuration;
use App\Classification;
use App\CurationStatus;
use App\Gci\GciMessage;
use App\ModeOfInheritance;
use Illuminate\Bus\Queueable;
use InvalidArgumentException;
use App\IncomingStreamMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateGciCurationFromStreamMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $ism;
    private $affiliations;
    private $statuses;
    private $classifications;
    private $mois;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(IncomingStreamMessage $ism)
    {
        $this->ism = $ism;
        $this->statuses = collect();
        $this->classifications = collect();
        $this->mois = collect();
        $this->affiliations = collect();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->ism->gdm_uuid) {
            return;
        }

        $this->populateLookups();

        $gciMessage = new GciMessage($this->ism->payload);
        $gciCuration = GciCuration::findByUuid($gciMessage->getUuid());
        $newData = [
            'mondo_id' => $gciMessage->mondoId,
            'moi_id' => $this->mois->get($gciMessage->getMoi())->id,
            'classification_id' => $this->getClassificationId($gciMessage->getClassification()),
            'status_id' => $this->getStatusId($gciMessage->getStatus()),
            'affiliation_id' => $this->getAffiliationId($gciMessage->getAffiliation()->id),
            'updated_at' => $gciMessage->getMessageDate(),
        ];

        if (!$gciCuration || $gciMessage->getStatus() == 'created') {
            $newData['hgnc_id'] = substr($gciMessage->getHgncId(), 5);
            $newData['creator_uuid'] = $gciMessage->getCreator()->id;
            $newData['creator_email'] = $gciMessage->getCreator()->email;
            $newData['created_at'] = $gciMessage->getMessageDate();
            $newData['gdm_uuid'] = $gciMessage->getUuid();
            $gciCuration = GciCuration::firstOrCreate(['gdm_uuid' => $gciMessage->getUuid()], $newData);
            return;
        }

        if (!$gciCuration) {
            throw new \Exception('GciCuration for gdm_uuid '.$this->ism->gdm_uuid.' not found. Status: '.$gciMessage->getStatus());
            return;
        }

        $gciCuration->fill($newData);
        $gciCuration->save();
    }

    private function getStatusId($name)
    {
        $name = str_replace('_', ' ', strtolower($name));
        if (!$this->statuses->get($name)) {
            throw new InvalidArgumentException($name.' not found in statuses table');
        }
        return $this->statuses->get($name)->id;
    }
    
    private function getClassificationId($name)
    {
        $name = str_replace('_', ' ', strtolower($name));
        if (empty($name) || $name == 'no classification') {
            return null;
        }
        if (!$this->classifications->get($name)) {
            throw new InvalidArgumentException($name.' not found in classifications table');
        }
        return $this->classifications->get($name)->id;
    }
    
    private function getAffiliationId($affiliationId)
    {
        if (empty($affiliationId)) {
            return null;
        }
        if (!$this->affiliations->get($affiliationId)) {
            throw new InvalidArgumentException($affiliationId.' not found in affiliations table');
        }
        return $this->affiliations->get($affiliationId)->id;
    }

    private function getMoiId($moiString)
    {
        return $this->mois->get($this->parseMoi($moiString))->id;
    }

    private function parseMoi($moiString)
    {
        $matches = [];
        preg_match('/\w*\((HP:\d{7})\)$/', $moiString, $matches);
        if (count($matches) != 2) {
            if ($moiString == 'Other') {
                return 'Other';
            }
            throw new InvalidArgumentException('Failed to parse MOI string '.$moiString);
        }
        return $matches[1];
    }

    private function populateLookups()
    {
        $this->mois = ModeOfInheritance::all()->keyBy('hp_id');
        $this->mois->put('Other', $this->mois['HP:0000000']);
        $this->mois->put('other', $this->mois['HP:0000000']);

        $this->statuses = CurationStatus::all()
                            ->map(function ($st) {
                                $st->name = strtolower($st->name);
                                return $st;
                            })
                            ->keyBy('name');
                            
        $this->statuses->put('none', $this->statuses['uploaded']);
        $this->statuses->put('in progress', $this->statuses['precuration complete']);
        $this->statuses->put('created', $this->statuses['precuration complete']);
        $this->statuses->put('approved', $this->statuses['curation approved']);
        $this->statuses->put('provisional', $this->statuses['curation provisional']);
        $this->statuses->put('provisionally approved', $this->statuses['curation provisional']);
        $this->statuses->put('unpublished', $this->statuses['precuration complete']);

        $this->classifications = Classification::all()
                                    ->map(function ($cl) {
                                        $cl->name = strtolower($cl->name);
                                        return $cl;
                                    })
                                    ->keyBy('name');
        $this->classifications->put('no reported evidence', $this->classifications['no known disease relationship']);

        $this->affiliations = Affiliation::all()->keyBy('clingen_id');
    }
}
