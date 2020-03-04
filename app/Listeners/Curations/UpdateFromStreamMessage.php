<?php

namespace App\Listeners\Curations;

use App\Curation;
use App\StreamError;
use App\Events\StreamMessages\Received;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exceptions\UnmatchableCurationException;

class UpdateFromStreamMessage
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Received  $event
     * @return void
     */
    public function handle(Received $event)
    {
        $payload = json_decode($event->message->payload);

        try {
            $curation = $this->matchCuration($payload);
        } catch (UnmatchableCurationException $e) {
            StreamError::create([
                'type' => 'unmatchable curation',
                'message_payload' => $e->getPayload(),
                'direction' => 'incoming',
            ]);
        }
        // if ($message->status == 'created') {
        //     $curation = Curation::where([
        //         'hgnc_id' => $message->
        //     ])
        // }

        // $curation = Curation::findByGuid($message->report_id);
        // if (!$curation) {
        //     throw new UnkownCurationRecordException();
        // }
    }

    private function matchCuration($payload)
    {
        $curation = Curation::findByUuid($payload->report_id);
        if (!$curation) {
            $curation = Curation::findByHgncAndMondo(
                $payload->gene_validity_evidence_level->genetic_condition->gene,
                $payload->gene_validity_evidence_level->genetic_condition->condition
                        );
            if (!$curation) {
                throw new UnmatchableCurationException($payload);
            }
        }
    }
}
