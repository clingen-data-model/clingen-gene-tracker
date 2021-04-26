<?php

namespace App\Jobs;

use App\Curation;
use App\Affiliation;
use App\Gci\GciMessage;
use App\ModeOfInheritance;
use Illuminate\Bus\Queueable;
use App\Services\GciStatusMap;
use App\Gci\GciClassificationMap;
use App\Jobs\Curations\AddStatus;
use App\Exceptions\GciSyncException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Curations\AddClassification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Contracts\GeneValidityCurationUpdateJob;

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
        $affiliation = Affiliation::findByClingenId($this->gciMessage->affiliation->id);
        $moi = ModeOfInheritance::findByHpId($this->gciMessage->moi);

        $this->curation->update([
            'gdm_uuid' => $this->gciMessage->uuid,
            'affiliation_id' => ($affiliation) ? $affiliation->id : null,
            'moi_id' => $moi->id,
            'mondo_id' => $this->gciMessage->mondoId
        ]);

        if ($this->gciMessage->status == 'created') {
            return;
        }

        AddStatus::dispatch(
            $this->curation,
            $this->statusMap->get($this->gciMessage->status),
            $this->gciMessage->statusDate
        );

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
