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
        $this->date = $this->resolveDate($date);
        $this->source = $source;
        $this->sourceEventKey = $sourceEventKey ?? $this->defaultSourceEventKey();
    }

    /**
     * A bare "Y-m-d" is what a curator's date picker and a bulk upload row supply
     * -- no time of day was ever known. Anchoring that at midnight misdates it for
     * anyone reading it in UTC (Eastern midnight reads as the previous evening),
     * so a past date is anchored at noon Eastern instead, which stays the same
     * calendar day under any reasonable timezone reinterpretation. Today's date is
     * given the actual current time, since it's being entered live.
     *
     * A caller passing anything else -- a GCI message's own timestamp, say -- is
     * trusted as-is; only a literal bare date triggers this.
     */
    private function resolveDate($date): Carbon
    {
        if (!$date) {
            return now();
        }

        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::parse($date);
        }

        $parsed = Carbon::parse($date);

        return $parsed->isSameDay(now()) ? now() : $parsed->setTime(12, 0, 0);
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
