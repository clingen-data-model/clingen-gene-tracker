<?php

namespace App\Jobs\Curations;

use App\Curation;
use Illuminate\Bus\Queueable;
use App\Contracts\MondoClient;
use Illuminate\Queue\SerializesModels;
use App\Mail\Curations\MondoIdNotFound;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\SendCurationMailToCoordinators;

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
        try {
            $mondoRecord = $mondoClient->fetchRecord($this->curation->numericMondoId);
            $this->curation->update(['mondo_name' => $mondoRecord->label]);
        } catch (\Throwable $th) {
            SendCurationMailToCoordinators::dispatch($this->curation, MondoIdNotFound::class);
            throw $th;
        }
    }
}
