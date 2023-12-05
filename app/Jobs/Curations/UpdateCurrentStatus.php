<?php

namespace App\Jobs\Curations;

use App\Curation;
use Illuminate\Foundation\Bus\Dispatchable;

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
     */
    public function handle(): void
    {
        // \Log::debug('updating current status');
        $curationStatuses = $this->curation
            ->curationStatuses()
            ->orderBy('status_date')
            ->orderBy('curation_curation_status.curation_status_id')
            ->orderBy('curation_curation_status.created_at')
            ->get();
        $currentStatus = $curationStatuses->last();

        $this->curation->update([
            'curation_status_id' => $currentStatus->id,
        ]);
    }
}
