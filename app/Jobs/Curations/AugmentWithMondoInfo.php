<?php

namespace App\Jobs\Curations;

use App\Curation;
use Illuminate\Bus\Queueable;
use App\Contracts\MondoClient;
use App\Events\Curation\Saved;
use App\Events\Curation\Updated;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\SendCurationMailToCoordinators;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\MondoIdNotFound;
use Illuminate\Contracts\Events\Dispatcher;

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
            if (empty($this->curation->numericMondoId)) {
                return;
            }
            $mondoRecord = $mondoClient->fetchRecord($this->curation->numericMondoId);
            $this->curation->update(['mondo_name' => $mondoRecord->label]);
        } catch (\Throwable $th) {
            NotifyCoordinatorsAboutCuration::dispatch($this->curation, MondoIdNotFound::class);
        }
    }
}
