<?php

namespace App\Jobs\Curation;

use App\Contracts\MondoClient;
use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AugmentWithMondoInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        //
        $this->curation = $curation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MondoClient $mondoClient)
    {
        $mondoRecord = $mondoClient->fetchRecord($this->curation->numericMondoId);
        $this->curation->update(['mondo_name' => $mondoRecord->label]);
    }
}
