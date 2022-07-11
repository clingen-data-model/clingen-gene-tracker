<?php

namespace App\Listeners\Curations;

use App\Curation;
use App\Contracts\GeneValidityCurationUpdateJob;
use App\StreamError;
use App\Gci\GciMessage;
use App\DataExchange\Events\Received;
use App\Exceptions\UnmatchableCurationException;
use Illuminate\Contracts\Bus\Dispatcher;

class UpdateFromStreamMessage
{
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

        $job = app()->makeWith(
                GeneValidityCurationUpdateJob::class, 
                [
                    'curation' => $curation, 
                    'gciMessage' => $gciMessage
                ]
            );

        $this->dispatcher->dispatch($job);
    }

    private function matchCuration(GciMessage $message)
    {
        $curation = Curation::findByGdmUuid($message->uuid);
        if (!$curation) {
            $curation = Curation::hgncAndMondo($message->hgncId, $message->mondoId)
                            ->noGdmUuid()
                            // ->whereHas('expertPanel', function ($q) use ($message) {
                            //     $q->where('affiliation_id', $message->affiliation->id);
                            // })
                            ->first();
            
            if (!$curation) {
                $curation = $this->findByHgncAndOriginalMondo($message);
            }
                            
            if (!$curation) {
                throw new UnmatchableCurationException($message->payload);
            }
        }

        return $curation;
    }

    private function findByHgncAndOriginalMondo(GciMessage $message)
    {
        if (!$message->isDiseaseChange()) {
            return null;
        }

        return Curation::findByHgncAndMondo($message->hgncId, $message->originalDisease);
    }
    
}
