<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use App\CurationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $curation;

    public $curationStatus;

    public $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, CurationStatus $curationStatus, string $date = null)
    {
        //
        $this->curation = $curation;
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
        if ($this->isCurrentStatus() || $this->isPreviousDatedStatus()) {
            return;
        }

        $this->curation->statuses()->attach([
            $this->curationStatus->id => [
                'status_date' => $this->date
            ]
        ]);
    }

    private function isCurrentStatus()
    {
        return $this->curation->currentStatus && $this->curation->currentStatus->id == $this->curationStatus->id;
    }

    private function isPreviousDatedStatus()
    {        
        $filtered = $this->curation->statuses->filter( function ($status) {
                return $status->id == $this->curationStatus->id
                    && $status->pivot->status_date == $this->date;
            });

        return $filtered->count() > 0;
    }
    
}
