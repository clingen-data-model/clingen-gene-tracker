<?php

namespace App\Jobs;

use App\Affiliation;
use App\Curation;
use App\ExpertPanel;
use App\Gci\GciClassificationMap;
use App\Gci\GciMessage;
use App\ModeOfInheritance;
use App\DataExchange\Contracts\GeneValidityCurationUpdateJob;
use App\DataExchange\Maps\GciStatusMap;
use App\Exceptions\GciSyncException;
use App\Jobs\Curations\AddClassification;
use App\Jobs\Curations\AddStatus;
use App\Jobs\Curations\SetOwner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateCurationFromGeneValidityMessage implements ShouldQueue, GeneValidityCurationUpdateJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;
    protected $gciMessage;
    protected $statusMap;
    protected $classificationMap;

    public function __construct(GciStatusMap $statusMap, GciClassificationMap $classificationMap, Curation $curation, GciMessage $gciMessage)
    {
        $this->curation = $curation;
        $this->gciMessage = $gciMessage;
        $this->statusMap = $statusMap;
        $this->classificationMap = $classificationMap;
    }

    public function handle()
    {
        $this->linkToGdm();
        $this->updateScalarFields();

        if ($this->gciMessage->isCreate()) {
            $this->advanceWatermark();
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

        $this->advanceWatermark();
    }

    private function linkToGdm(): void
    {
        if (is_null($this->curation->gdm_uuid)) {
            $this->curation->update(['gdm_uuid' => $this->gciMessage->uuid]);
        }
    }

    /**
     * These fields keep no history, so the only thing standing between a replayed
     * old message and a stale overwrite is the watermark.
     */
    private function updateScalarFields(): void
    {
        if ($this->isStale()) {
            Log::info('GCI sync: skipping scalar update from a message older than the curation watermark', [
                'gdm_uuid' => $this->gciMessage->uuid,
                'message_date' => (string) $this->gciMessage->messageDate,
                'watermark' => (string) $this->curation->gci_event_watermark,
            ]);

            return;
        }

        $attributes = ['mondo_id' => $this->gciMessage->mondoId];

        // A lookup that fails is not an assertion that the value is null, so leave
        // the existing value alone rather than clearing it.
        if ($affiliation = $this->findAffiliation()) {
            $attributes['affiliation_id'] = $affiliation->id;
        }

        if ($moi = $this->findMoi()) {
            $attributes['moi_id'] = $moi->id;
        }

        $changed = array_filter(
            $attributes,
            fn ($value, $key) => $this->curation->{$key} != $value,
            ARRAY_FILTER_USE_BOTH
        );

        // Writing values the curation already holds would fire Updated and announce
        // a precuration change that did not happen.
        if (empty($changed)) {
            return;
        }

        $this->curation->update($changed);
    }

    private function isStale(): bool
    {
        return $this->curation->gci_event_watermark
            && $this->gciMessage->messageDate->lt($this->curation->gci_event_watermark);
    }

    private function advanceWatermark(): void
    {
        $watermark = $this->gciMessage->messageDate;

        if ($this->curation->gci_event_watermark
            && !$watermark->gt($this->curation->gci_event_watermark)
        ) {
            return;
        }

        // Bookkeeping about what we have consumed, not curation content. Writing it
        // through the model would fire Updated, and so announce a precuration change
        // to the data exchange for every message consumed.
        $value = $watermark->format('Y-m-d H:i:s');

        DB::table('curations')
            ->where('id', $this->curation->getKey())
            ->update(['gci_event_watermark' => $value]);

        $this->curation->setAttribute('gci_event_watermark', $value);
        $this->curation->syncOriginalAttribute('gci_event_watermark');
    }

    private function findAffiliation(): ?Affiliation
    {
        $clingenId = $this->gciMessage->affiliation->id;
        $affiliation = Affiliation::findByClingenId($clingenId);

        if (!$affiliation) {
            Log::warning('GCI sync: Affiliation not found for ClinGen affiliation id', [
                'affiliation_id' => $clingenId,
                'curation_uuid' => $this->curation->uuid ?? null,
                'gdm_uuid' => $this->gciMessage->uuid ?? null,
            ]);
        }

        return $affiliation;
    }

    private function findMoi(): ?ModeOfInheritance
    {
        $moi = ModeOfInheritance::findByHpId($this->gciMessage->moi);

        if (!$moi) {
            Log::warning('GCI sync: Mode of inheritance not found', [
                'moi' => $this->gciMessage->moi,
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
            $this->gciMessage->statusDate,
            'gci',
            $this->gciMessage->sourceKey
        );
    }

    private function addClassification()
    {
        try {
            AddClassification::dispatchSync(
                $this->curation,
                $this->classificationMap->get($this->gciMessage->classification),
                $this->gciMessage->statusDate,
                'gci',
                $this->gciMessage->sourceKey
            );
        } catch (GciSyncException $e) {
            report($e);
        }
    }

    private function transferRecord()
    {
        $gcepID = $this->gciMessage->content->transfer_to->gcep_id;
        $newExpertPanel = ExpertPanel::findByAffiliationId($gcepID);

        if (!$newExpertPanel) {
            Log::warning('GCI transfer: ExpertPanel not found for affiliation id, possibly related to GT-83', [
                'gcep_id' => $gcepID,
                'curation_uuid' => $this->curation->uuid ?? null,
                'gdm_uuid' => $this->gciMessage->uuid ?? null,
            ]);

            return;
        }

        // The transfer happened when the message says it did, not when we processed
        // it, or a replay would stamp today's date on a years-old transfer.
        // Called directly so the return value is the handler's. dispatchSync only
        // gives that back for a job that is not ShouldQueue, which is too subtle a
        // thing to depend on.
        $recorded = (new SetOwner(
            $this->curation,
            $newExpertPanel->id,
            $this->gciMessage->messageDate,
            null,
            'gci',
            $this->gciMessage->sourceKey
        ))->handle();

        // Only note a transfer we actually recorded; replays would otherwise
        // accumulate a fresh note every time.
        if ($recorded && $this->gciMessage->hasContentNotes()) {
            dispatch(new AddNote(
                subject: $this->curation,
                content: $this->gciMessage->getContentNotes(),
                topic: 'curation transfer (via GCI)',
                author: null
            ));
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
}
