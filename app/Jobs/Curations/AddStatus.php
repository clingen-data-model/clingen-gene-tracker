<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\CurationStatus;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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
        if ($this->hasCurationCurationStatus() && ($this->isCurrentStatus() || $this->isPreviousDatedStatus())) {
            return;
        }

        DB::transaction(function () {
            $this->curation->statuses()->attach([
                $this->curationStatus->id => [
                    'status_date' => $this->date->startOfDay(),
                ],
            ]);

            UpdateCurrentStatus::dispatchNow($this->curation);
        });
    }

    private function isCurrentStatus()
    {
        return $this->curation->currentStatus && $this->curation->currentStatus->id == $this->curationStatus->id;
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
}
