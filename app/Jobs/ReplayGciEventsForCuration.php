<?php

namespace App\Jobs;

use Exception;
use App\Curation;
use App\Gci\GciMessage;
use Illuminate\Bus\Queueable;
use App\IncomingStreamMessage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Contracts\GeneValidityCurationUpdateJob;

class ReplayGciEventsForCuration implements ShouldQueue
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
    public function handle()
    {
        if (!$this->curation->gdm_uuid) {
            throw new Exception('Curation '.$this->curation->id.' is not linked to a GDM.');
            return 1;
        }

        $isms = IncomingStreamMessage::where('gdm_uuid', $this->curation->gdm_uuid)->get();
        $isms->map(function ($msg) {
            return new GciMessage($msg->payload);
        })
            ->each(function ($gciMsg) {
                $job = app()->makeWith(
                    GeneValidityCurationUpdateJob::class,
                    [
                        'curation' => $this->curation,
                        'gciMessage' => $gciMsg
                    ]
                );
                Bus::dispatch($job);
            });

        \Log::info('Replayed '.$isms->count().' gene_validity_events_messages for curation '.$this->curation->id);
    }
}
