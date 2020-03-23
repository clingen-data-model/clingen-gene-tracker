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
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->curation->currentStatus && $this->curation->currentStatus->id == $this->curationStatus->id) {
            return;
        }

        $this->curation->statuses()->attach([
            $this->curationStatus->id => [
                'status_date' => Carbon::parse($this->date)
            ]
        ]);
    }
}
