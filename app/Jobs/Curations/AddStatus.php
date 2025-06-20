<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use App\CurationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Curations\UpdateCurrentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class AddStatus implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $curation;
    public $curationStatus;
    public $date;
    private $previousStatus;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, CurationStatus $curationStatus, $date = null)
    {
        $this->curation = $curation;
        $this->previousStatus = $this->curation->fresh()->currentStatus;
        $this->curationStatus = $curationStatus;
        $this->date = Carbon::parse($date)->startOfDay();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->hasCurationCurationStatus() && $this->isCurrentStatus()) { return; }
        if($this->isSameStatusOnDate()) { return; }

        DB::transaction(function () {
            $this->curation->statuses()->attach([
                $this->curationStatus->id => [
                    'status_date' => $this->date->startOfDay(),
                ],
            ]);
            UpdateCurrentStatus::dispatchSync($this->curation);
        });
    }

    private function isCurrentStatus()
    {
        return $this->hasCurationCurationStatus() && $this->curation->curationStatuses->first()->id == $this->curationStatus->id;
    }

    private function hasCurationCurationStatus()
    {
        return $this->curation->curationStatuses->count() > 0;
    }

    private function isPreviousDatedStatus()
    {
        $filtered = $this->curation->statuses->filter(function ($status) {
            return $status->id == $this->curationStatus->id
                    && $status->pivot->status_date->format('Y-m-d H:i:s') == Carbon::parse($this->date)->format('Y-m-d H:i:s');
        });
        return $filtered->count() > 0;
    }

    // CHECKING THE LATEST STATUS ON THE GIVEN DATE
    private function isSameStatusOnDate()
    {
        $filtered = $this->curation->statuses->filter(function ($status) {
            return $status->pivot->status_date->format('Y-m-d H:i:s') == Carbon::parse($this->date)->format('Y-m-d H:i:s');
        })->first();
        if ($filtered && $filtered->id == $this->curationStatus->id) {
            return 1;
        }
        return 0;
    }

}
