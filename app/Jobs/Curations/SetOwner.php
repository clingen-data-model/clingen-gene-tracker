<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\Curations\TransferNotification;

class SetOwner
{
    use Dispatchable;

    protected Curation $curation;
    protected int $expertPanelId;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected array $curationUpdates;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, int $expertPanelId, $startDate, $endDate = null, array $curationUpdates = [])
    {
        $this->curation = $curation;
        $this->expertPanelId = $expertPanelId;
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = $endDate ? Carbon::parse($endDate) : $this->startDate->copy();
        $this->curationUpdates = $curationUpdates;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Initial ownership: curation already has its owner,
        // but no ownership history has been created yet.
        if (
            $this->curation->expert_panel_id == $this->expertPanelId
            && ! $this->curation->expertPanels()->exists()
        ) {
            $this->addNewOwner();
            return;
        }
        
        // I can imagine a GCI transfer message says the destination ExpertPanel is already the curation's current ExpertPanel.
        // Or in case there are 2 exactly the same messages from GCI then this $this->curation->expert_panel_id != $this->expertPanelId will return false.
        // We don't need to: close ownership, add ownership, send transfer email but we still want to process any other curation updates that may be present in the message.
        if (
            ($this->expertPanelId && $this->curation->expert_panel_id != $this->expertPanelId)
            || $this->curation->isDirty('expert_panel_id')
        ) {
            \DB::transaction(function () {
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
        } elseif (!empty($this->curationUpdates)) {
            $this->curation->update($this->curationUpdates);
        }
    }

    private function setEndOfOwnership()
    {
        $currentOwnerId = $this->curation->expert_panel_id;

        $updated = \DB::table('curation_expert_panel')
            ->where('curation_id', $this->curation->id)
            ->where('expert_panel_id', $currentOwnerId)
            ->whereNull('end_date')
            ->update([
                'end_date' => $this->endDate->toDateString(),
            ]);

        if ($updated) {
            return;
        }

        // Repair missing history row for legacy/bad data
        $this->curation->expertPanels()->attach([
            $currentOwnerId => [
                'start_date' => optional($this->curation->created_at)->toDateString(),
                'end_date' => $this->endDate->toDateString(),
            ]
        ]);
    }
    
    private function addNewOwner()
    {
        $hasOpenOwnership = \DB::table('curation_expert_panel')->where('curation_id', $this->curation->id)->where('expert_panel_id', $this->expertPanelId)->whereNull('end_date')->exists();
        if (!$hasOpenOwnership) {
            $this->curation->expertPanels()->attach([
                $this->expertPanelId => [
                    'start_date' => $this->startDate->toDateString(),
                    'end_date' => null,
                ]
            ]);
        }

        $this->curation->update(array_merge(
            $this->curationUpdates,
            ['expert_panel_id' => $this->expertPanelId]
        ));
    }    
}
