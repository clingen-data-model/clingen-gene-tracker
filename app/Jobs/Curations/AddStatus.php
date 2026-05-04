<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use App\CurationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Curations\UpdateCurrentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddStatus implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $curation;
    public $curationStatus;
    public $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, CurationStatus $curationStatus, $date = null)
    {
        $this->curation = $curation;
        $this->curationStatus = $curationStatus;
        $this->date = $date ? Carbon::parse($date) : now();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->isExistingDatedStatus()) { return; }

        if ($this->isCurrentStatus() && $this->isExistingSameDayStatus()) { return; }

        $this->curation->statuses()->attach([
            $this->curationStatus->id => [
                'status_date' => $this->date,
            ],
        ]);
        UpdateCurrentStatus::dispatchSync($this->curation->fresh());
    }

    private function isCurrentStatus()
    {
        $previousStatus = $this->curation->curationStatuses()
            ->wherePivot('status_date', '<', $this->date->format('Y-m-d H:i:s'))
            ->reorder()
            ->orderBy('curation_curation_status.status_date', 'desc')
            ->orderBy('curation_curation_status.id', 'desc')
            ->first();

        return $previousStatus && $previousStatus->id == $this->curationStatus->id;
    }

    private function isExistingDatedStatus()
    {
        return $this->curation->curationStatuses()
            ->where('curation_statuses.id', $this->curationStatus->id)
            ->wherePivot('status_date', $this->date->format('Y-m-d H:i:s'))
            ->exists();
    }

    private function isExistingSameDayStatus()
    {
        return $this->curation->curationStatuses()
            ->where('curation_statuses.id', $this->curationStatus->id)
            ->whereDate('curation_curation_status.status_date', $this->date->toDateString())
            ->exists();
    }
}