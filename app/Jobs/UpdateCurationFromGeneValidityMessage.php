<?php

namespace App\Jobs;

use App\Curation;
use App\Affiliation;
use App\ExpertPanel;
use App\Gci\GciMessage;
use App\ModeOfInheritance;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Jobs\Curations\SetOwner;
use App\Gci\GciClassificationMap;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Exceptions\GciSyncException;
use Illuminate\Queue\SerializesModels;
use App\DataExchange\Maps\GciStatusMap;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Curations\AddClassification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\DataExchange\Contracts\GeneValidityCurationUpdateJob;


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
    public function handle()
    {
        $moi = $this->findMoi();

        // Handle transfers separately so ownership fields are updated together. GT-73
        if ($this->gciMessage->isGdmTransfer()) {
            $this->transferRecord($moi);
        } else {
            $affiliation = $this->findAffiliation();
            $this->curation->update([
                'gdm_uuid' => $this->gciMessage->uuid,
                'affiliation_id' => $affiliation?->id,
                'moi_id' => $moi?->id,
                'mondo_id' => $this->gciMessage->mondoId,
            ]);
        }

        if ($this->gciMessage->isCreate()) {
            return;
        }

        if ($this->gciMessage->isDiseaseChange()) {
            $this->updateDisease();
        }

        if ($this->gciMessage->hasStatus()) {
            $this->addStatus();
            $this->addClassification();
        }
    }

    // Other than transfer, the affiliation is in the performed_by.on_behalf_of field of the payload. GT-73
    private function findAffiliation(): ?Affiliation
    {
        return $this->findAffiliationByClingenId($this->gciMessage->affiliation?->id, 'performed_by.on_behalf_of');
    }

    // Affiliation for transfer is in a different place in the payload than for non-transfer messages, so we need a separate method to find it. GT-73
    private function findTransferAffiliation(): ?Affiliation
    {
        return $this->findAffiliationByClingenId($this->gciMessage->transferToId, 'content.transfer_to.id');
    }

    private function findAffiliationByClingenId(?string $clingenId, string $source): ?Affiliation
    {
        if (!$clingenId) {
            Log::warning('GCI sync: Affiliation ID missing from message', [
                'source' => $source,
                'curation_uuid' => $this->curation->uuid ?? null,
                'gdm_uuid' => $this->gciMessage->uuid ?? null,
            ]);
            return null;
        }

        $affiliation = Affiliation::findByClingenId($clingenId);
        if (!$affiliation) {
            Log::warning('GCI sync: Affiliation not found for ClinGen affiliation id', [
                'affiliation_id' => $clingenId,
                'source' => $source,
                'curation_uuid' => $this->curation->uuid ?? null,
                'gdm_uuid' => $this->gciMessage->uuid ?? null,
            ]);
        }
        return $affiliation;
    }

    private function findMoi(): ?ModeOfInheritance
    {
        $hpId = $this->gciMessage->moi;
        if (!$hpId) {
            Log::warning('GCI sync: Mode of inheritance missing from message', [
                'curation_uuid' => $this->curation->uuid ?? null,
                'gdm_uuid' => $this->gciMessage->uuid ?? null,
            ]);
            return null;
        }

        $moi = ModeOfInheritance::findByHpId($hpId);
        if (!$moi) {
            Log::warning('GCI sync: Mode of inheritance not found', [
                'moi' => $hpId,
                'curation_uuid' => $this->curation->uuid ?? null,
                'gdm_uuid' => $this->gciMessage->uuid ?? null,
            ]);
        }
        return $moi;
    }

    private function addStatus()
    {
        if ($this->shouldIgnoreStatus($this->gciMessage->getStatus())) {
            return;
        }

        AddStatus::dispatchSync(
            $this->curation,
            $this->statusMap->get($this->gciMessage->status),
            $this->gciMessage->statusDate
        );
    }

    private function transferRecord(?ModeOfInheritance $moi): void
    {
        $gcepId = $this->gciMessage->transferToGcepId;
        $newExpertPanel = $gcepId ? ExpertPanel::findByAffiliationId($gcepId) : null;
        
        if ($newExpertPanel) {
            $affiliation = $this->findTransferAffiliation();

            SetOwner::dispatch(
                $this->curation,
                $newExpertPanel->id,
                $this->gciMessage->transferDate,
                null,
                [
                    'gdm_uuid' => $this->gciMessage->uuid,
                    'affiliation_id' => $affiliation?->id,
                    'moi_id' => $moi?->id,
                    'mondo_id' => $this->gciMessage->mondoId,
                ]
            );

            if ($this->gciMessage->hasContentNotes()) {
                $job = new AddNote(
                    subject: $this->curation,
                    content: $this->gciMessage->contentNotes,
                    topic: 'curation transfer (via GCI)',
                    author: null
                );

                dispatch($job);
            }
        } else {
            Log::warning('GCI transfer: ExpertPanel not found for affiliation id, possibly related to GT-83', [
                'gcep_id' => $gcepId,
                'transfer_from_gcep_id' => $this->gciMessage->transferFromGcepId,
                'curation_uuid' => $this->curation->uuid ?? null,
                'gdm_uuid' => $this->gciMessage->uuid ?? null,
            ]);

            $this->curation->update([
                'gdm_uuid' => $this->gciMessage->uuid,
                'moi_id' => $moi?->id,
                'mondo_id' => $this->gciMessage->mondoId,
            ]);
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
