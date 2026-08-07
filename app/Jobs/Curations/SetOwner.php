<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\Curations\TransferNotification;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\CurationExpertPanel;

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
        $this->endDate = $endDate ? Carbon::parse($endDate) : null;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->curation->expert_panel_id == $this->expertPanelId) {
            $this->ensureCurrentOwnerHistory();
            return;
        }

        \DB::transaction(function () {
            $originalOwner = $this->curation->expertPanel;

            $this->setEndOfOwnership();
            $this->addNewOwner();

            $this->curation->refresh();
            if ($this->curation->expertPanel->hasCoordinators()) {
                Mail::to($this->curation->expertPanel->coordinators)->cc($originalOwner->coordinators)->send(new TransferNotification($this->curation->fresh(), $originalOwner));
            }

        });
    }

    private function setEndOfOwnership()
    {
        $currentOwnerId = $this->curation->expert_panel_id;
        $existing = $this->curation->expertPanels()->where('expert_panels.id', $currentOwnerId)->wherePivotNull('end_date')->first();
        if ($existing) {
            CurationExpertPanel::where('id', $existing->pivot->id)->update(['end_date' => $this->endDate]);
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
        $this->curation->expertPanels()->attach([
            $this->expertPanelId => [
                'start_date'=> $this->startDate,
                'end_date' => null
            ]
        ]);
    
        if ($this->curation->expert_panel_id != $this->expertPanelId) {
            $this->curation->update(['expert_panel_id' => $this->expertPanelId]);
        }
    }

    private function ensureCurrentOwnerHistory()
    {
        $existing = $this->curation->expertPanels()->where('expert_panels.id', $this->expertPanelId)->wherePivotNull('end_date')->exists();
        if ($existing) { return; }

        $this->curation->expertPanels()->attach([
            $this->expertPanelId => [
                'start_date' => $this->startDate,
                'end_date' => null,
            ]
        ]);
    }
    
}
