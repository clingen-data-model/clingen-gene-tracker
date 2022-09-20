<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\Curations\TransferNotification;
use App\Jobs\NotifyCoordinatorsAboutCuration;

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

        }
    }

    private function setEndOfOwnerShip()
    {
        $this->curation->expertPanels()
            ->updateExistingPivot(
                $this->curation->expert_panel_id, 
                ['end_date'=>($this->endDate)]
            );
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
