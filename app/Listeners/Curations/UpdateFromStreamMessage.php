<?php

namespace App\Listeners\Curations;

use App\Curation;
use App\Affiliation;
use App\Contracts\GeneValidityCurationUpdateJob;
use App\StreamError;
use App\Gci\GciMessage;
use App\ModeOfInheritance;
use App\Services\GciStatusMap;
use App\Gci\GciClassificationMap;
use App\Jobs\Curations\AddStatus;
use App\Events\StreamMessages\Received;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Curations\AddClassification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exceptions\UnmatchableCurationException;
use Illuminate\Contracts\Bus\Dispatcher;

class UpdateFromStreamMessage
{
    private $statusMap;
    private $classificationMap;
    private $dispatcher;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the event.
     *
     * @param  Received  $event
     * @return void
     */
    public function handle(Received $event)
    {
        $gciMessage = new GciMessage($event->message->payload);

        try {
            $curation = $this->matchCuration($gciMessage);
        } catch (UnmatchableCurationException $e) {
            StreamError::create([
                'type' => 'unmatchable curation',
                'message_payload' => $e->getPayload(),
                'direction' => 'incoming',
            ]);
            return;
        }

        $job = app()->makeWith(GeneValidityCurationUpdateJob::class, ['curation' => $curation, 'gciMessage' => $gciMessage]);
        $this->dispatcher->dispatch($job);
    }

    private function matchCuration(GciMessage $message)
    {
        $curation = Curation::findByUuid($message->uuid);
        if (!$curation) {
            $curation = Curation::hgncAndMondo($message->hgncId, $message->mondoId)
                            ->noUuid()
                            ->first();
                
            if (!$curation) {
                throw new UnmatchableCurationException($message->payload);
            }
        }

        return $curation;
    }
}
