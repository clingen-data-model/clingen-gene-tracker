<?php

namespace App\Jobs\Curations;

use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\Curations\CurationField;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Records a change of ownership.
 *
 * Ownership is stored as the point in time it began; the closing end_date of the
 * previous owner is derived by the projector from the next row. That is what makes
 * an out-of-order transfer, or a correction to a past one, safe -- and what allows
 * a curation to return to a panel it was owned by before.
 *
 * The transfer notification is no longer sent from here. It is a side effect of
 * ownership actually changing, so it hangs off CurrentOwnerChanged, which the
 * projector only fires when the current owner really moves.
 */
class SetOwner
{
    use Dispatchable;

    public $curation;
    public $expertPanelId;
    public $startDate;
    public $source;
    public $sourceEventKey;

    /**
     * @param mixed $endDate Deprecated and ignored; end dates are derived.
     */
    public function __construct(
        Curation $curation,
        int $expertPanelId,
        $startDate,
        $endDate = null,
        string $source = 'ui',
        ?string $sourceEventKey = null
    ) {
        $this->curation = $curation;
        $this->expertPanelId = $expertPanelId;
        $this->startDate = Carbon::parse($startDate);
        $this->source = $source;
        $this->sourceEventKey = $sourceEventKey ?? $this->defaultSourceEventKey();
    }

    public function handle()
    {
        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::ExpertPanel,
            $this->expertPanelId,
            $this->startDate,
            $this->source,
            $this->sourceEventKey
        );
    }

    private function defaultSourceEventKey(): string
    {
        return 'ui:expert_panel:'.$this->startDate->toDateString().':'.$this->expertPanelId;
    }
}
