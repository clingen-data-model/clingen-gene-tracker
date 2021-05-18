<?php

namespace App\Jobs\Curations;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCurrentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $currentStatus = $this->curation->curationStatuses
            ->sortByDesc(function ($item) {
                return $item->pivot->status_date->timestamp.'.'.$item->id;
            })
            ->first();

        $this->curation->update([
            'curation_status_id' => $currentStatus->id
        ]);
    }
}
