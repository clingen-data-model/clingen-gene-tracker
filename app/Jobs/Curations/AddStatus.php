<?php

namespace App\Jobs\Curations;

use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\CurationStatus;
use App\Curations\CurationField;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Records a status observation. Idempotency and projection live in
 * RecordCurationFieldEvent; this only supplies the source identity.
 */
class AddStatus implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $curation;
    public $curationStatus;
    public $date;
    public $source;
    public $sourceEventKey;

    public function __construct(
        Curation $curation,
        CurationStatus $curationStatus,
        $date = null,
        string $source = 'ui',
        ?string $sourceEventKey = null
    ) {
        $this->curation = $curation;
        $this->curationStatus = $curationStatus;
        $this->date = $date ? Carbon::parse($date) : now();
        $this->source = $source;
        $this->sourceEventKey = $sourceEventKey ?? $this->defaultSourceEventKey();
    }

    /**
     * @return bool Whether a new status event was recorded.
     */
    public function handle(): bool
    {
        return RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Status,
            $this->curationStatus->id,
            $this->date,
            $this->source,
            $this->sourceEventKey
        );
    }

    /**
     * Without an external event to key on, the assertion itself is the identity:
     * re-submitting the same status for the same day is the same assertion.
     */
    private function defaultSourceEventKey(): string
    {
        return 'ui:status:'.$this->date->toDateString().':'.$this->curationStatus->id;
    }
}
