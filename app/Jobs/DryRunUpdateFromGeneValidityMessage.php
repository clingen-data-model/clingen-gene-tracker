<?php

namespace App\Jobs;

use App\Curation;
use App\Affiliation;
use App\Contracts\GeneValidityCurationUpdateJob;
use App\Gci\GciMessage;
use App\ModeOfInheritance;
use Illuminate\Bus\Queueable;
use App\Services\GciStatusMap;
use App\Gci\GciClassificationMap;
use Illuminate\Foundation\Bus\Dispatchable;

class DryRunUpdateFromGeneValidityMessage implements GeneValidityCurationUpdateJob
{
    use Dispatchable, Queueable;

    protected $curation;
    protected $gciMessage;
    protected $statusMap;
    protected $classificationMap;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GciStatusMap $statusMap, GciClassificationMap $classificationMap, Curation $curation, GciMessage $gciMessage)
    {
        \Log::debug(__METHOD__);
        $this->curation = $curation;
        $this->gciMessage = $gciMessage;
        $this->statusMap = $statusMap;
        $this->classificationMap = $classificationMap;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::debug(__METHOD__);
        $affiliation = Affiliation::findByClingenId($this->gciMessage->affiliation->id);
        $moi = ModeOfInheritance::findByHpId($this->gciMessage->moi);

        $updateSummary = [
            'gdm_uuid' => $this->gciMessage->uuid,
            'affiliation_id' => $affiliation->id,
            'moi_id' => $moi->id,
        ];

        if ($this->gciMessage->status == 'created') {
            \Log::debug('consume GCI events dry run', $updateSummary);
            return;
        }
        $updateSummary['addStatus'] = [
            'status' => $this->statusMap->get($this->gciMessage->status->name),
            'status_date' => $this->gciMessage->messageDate
        ];

        $updateSummary['addClassification'] = [
            'classification' => $this->classificationMap->get($this->gciMessage->classification),
            'classification_date' => $this->gciMessage->messageDate
        ];

        \Log::debug('consume GCI events dry run', $updateSummary);
    }
}
