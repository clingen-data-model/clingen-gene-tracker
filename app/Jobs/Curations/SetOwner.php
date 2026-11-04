<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\Curations\TransferNotification;

class SetOwner
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, int $expertPanelId, $startDate, $endDate = null)
    {
        $this->curation = $curation;
        $this->expertPanelId = $expertPanelId;
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::transaction(function () {
            // Serialize transfers and initialization on the same persisted curation.
            Curation::whereKey($this->curation->id)->lockForUpdate()->firstOrFail();
            $this->curation->refresh();
            if ($this->curation->expert_panel_id == $this->expertPanelId) return;

            $originalOwner = $this->curation->expertPanel;
            $this->setEndOfOwnership();
            $this->addNewOwner();

            $this->curation->refresh();
            if ($this->curation->expertPanel->hasCoordinators()) {
                Mail::to($this->curation->expertPanel->coordinators)
                    ->cc($originalOwner->coordinators)
                    ->send(new TransferNotification($this->curation->fresh(), $originalOwner));
            }
        });
    }

    /** Initial ownership is not a transfer, even while the created model is dirty. */
    public static function initialize(Curation $curation): void
    {
        DB::transaction(function () use ($curation) {
            $persisted = Curation::whereKey($curation->id)->lockForUpdate()->firstOrFail();
            // Do not replay initialization over existing history or a later owner.
            if ($persisted->expert_panel_id != $curation->expert_panel_id
                || $persisted->expertPanels()->exists()) return;

            $persisted->expertPanels()->attach($persisted->expert_panel_id, [
                'start_date' => $persisted->created_at,
                'end_date' => null,
            ]);
        });
    }

    private function setEndOfOwnership()
    {
        $currentOwnerId = $this->curation->expert_panel_id;

        $existing = $this->curation->expertPanels()
            ->where('expert_panel_id', $currentOwnerId)
            ->first();

        if ($existing) {
            $this->curation->expertPanels()->updateExistingPivot(
                $currentOwnerId,
                ['end_date' => $this->endDate]
            );
            return;
        }

        // Repair missing history row for legacy/bad data
        $this->curation->expertPanels()->attach([
            $currentOwnerId => [
                'start_date' => optional($this->curation->created_at)->toDateString(),
                'end_date' => optional($this->endDate)->toDateString(),
            ]
        ]);
    }
    
    private function addNewOwner()
    {
        $this->curation->expertPanels()
        ->attach([
            $this->expertPanelId => [
                'start_date'=> $this->startDate,
                'end_date' => null
            ]
        ]);
    
        if ($this->curation->expert_panel_id != $this->expertPanelId) {
            $this->curation->update(['expert_panel_id' => $this->expertPanelId]);
        }
    }
    
}
