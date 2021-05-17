<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;

class SetOwner
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, $expertPanelId, $startDate, $endDate = null)
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
                $this->curation->expertPanels()
                    ->updateExistingPivot($this->curation->expert_panel_id, ['end_date'=>$this->endDate]);

                $this->curation->expertPanels()
                    ->attach([
                        $this->expertPanelId => [
                            'start_date'=> $this->startDate,
                            'end_date' => null
                        ]
                    ]);
                
                $this->curation->update(['expert_panel_id' => $this->expertPanelId]);
            });
        }
    }
}
