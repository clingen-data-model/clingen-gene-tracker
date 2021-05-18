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
        $this->date = Carbon::parse($date);
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
                    'status_date' => $this->date,
                ],
            ]);
            if ($this->isAfterPreviousStatus()) {
                UpdateCurrentStatus::dispatch($this->curation);
            }
        });
    }

    private function isAfterPreviousStatus()
    {
        if (!$this->previousStatus) {
            return true;
        }

        $status = $this->curation->statuses->keyBy('id')->get($this->previousStatus->id);
        if (!$status) {
            return true;
        }

        return $this->date->gt($status->pivot->status_date);
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
