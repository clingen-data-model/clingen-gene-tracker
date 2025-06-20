<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\CurationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCurrentStatus
{
    use Dispatchable;

    private Curation $curation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        $this->curation = $curation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Log::debug('updating current status');
        $currentStatus = $this->curation->curationStatuses()->first();

        if($currentStatus) {
            $this->curation->update([
                'curation_status_id' => $currentStatus->id
            ]);
        } else {
            return null;
        }
    }
}
