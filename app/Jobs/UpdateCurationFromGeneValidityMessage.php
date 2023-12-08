<?php

namespace App\Jobs;

use App\Affiliation;
use App\Curation;
use App\DataExchange\Contracts\GeneValidityCurationUpdateJob;
use App\DataExchange\Maps\GciStatusMap;
use App\Exceptions\GciSyncException;
use App\ExpertPanel;
use App\Gci\GciClassificationMap;
use App\Gci\GciMessage;
use App\Jobs\Curations\AddClassification;
use App\Jobs\Curations\AddStatus;
use App\Jobs\Curations\SetOwner;
use App\ModeOfInheritance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class UpdateCurationFromGeneValidityMessage implements ShouldQueue, GeneValidityCurationUpdateJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(): void
    {
        $affiliation = $this->findAffiliation();
        $moi = $this->findMoi();

        $this->curation->update([
            'gdm_uuid' => $this->gciMessage->uuid,
            'affiliation_id' => ($affiliation) ? $affiliation->id : null,
            'moi_id' => $moi->id,
            'mondo_id' => $this->gciMessage->mondoId,
        ]);

        if ($this->gciMessage->isCreate()) {
            return;
        }

        if ($this->gciMessage->isGdmTransfer()) {
            $this->transferRecord();
        }

        if ($this->gciMessage->isDiseaseChange()) {
            $this->updateDisease();
        }

        if ($this->gciMessage->hasStatus()) {
            $this->addStatus();
            $this->addClassification();
        }
    }

    private function findAffiliation(): Affiliation
    {
        return Affiliation::findByClingenId($this->gciMessage->affiliation->id);
    }

    private function findMoi(): ModeOfInheritance
    {
        return ModeOfInheritance::findByHpId($this->gciMessage->moi);
    }

    private function addStatus()
    {
        if ($this->shouldIgnoreStatus($this->gciMessage->getStatus())) {
            return;
        }

        AddStatus::dispatch(
            $this->curation,
            $this->statusMap->get($this->gciMessage->status),
            $this->gciMessage->statusDate
        );
    }

    private function transferRecord()
    {
        $newExpertPanel = ExpertPanel::findByAffiliationId($this->gciMessage->content->transfer_to->gcep_id);
        SetOwner::dispatch($this->curation, $newExpertPanel->id, Carbon::now());

        if ($this->gciMessage->hasContentNotes()) {
            $job = new AddNote(
                subject: $this->curation,
                content: 'Transferred from Test GCEP 2 to Test GCEP 1.',
                topic: 'curation transfer (via GCI)',
                author: null
            );

            dispatch($job);
        }
    }

    private function updateDisease()
    {
    }

    /**
     * gene_validity_events message sets status to 'gdm_transfered' and 'disease_changed'
     * for those two event types.  We don't have to set the curation status to either of those
     * because they are really event types.
     */
    private function shouldIgnoreStatus(string $status): bool
    {
        return $status == 'gdm_transferred' || $status == 'disease_changed';
    }

    private function addClassification()
    {
        try {
            AddClassification::dispatch(
                $this->curation,
                $this->classificationMap->get($this->gciMessage->classification),
                $this->gciMessage->statusDate
            );
        } catch (GciSyncException $e) {
            report($e);
        }
    }
}
