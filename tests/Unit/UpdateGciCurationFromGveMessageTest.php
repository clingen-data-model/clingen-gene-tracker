<?php

namespace Tests\Unit;

use App\DataExchange\Events\Received;
use App\Jobs\Gci\UpdateGciCurationFromStreamMessage;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class UpdateGciCurationFromGveMessageTest extends TestCase
{
    /**
     * @test
     */
    public function UpdateGciCurationFromGveMessage_listener_dispatches_UpdateGciCuratonFromMessage_job(): void
    {
        Bus::fake();
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->topic = config('dx.topics.incoming.gene_validity_events');
        $kafkaMessage->payload = json_encode([
            'status' => 'created',
            'report_id' => 'some-random-uuid',
            'gene_validity_evidence_level' => [
                'genetic_condition' => [
                    'gene' => 'HGNC:1234',
                    'condition' => 'MONDO:0000666',
                    'mode_of_inheritance' => 'HP:000005',
                ],
            ],

        ]);
        event(new Received($kafkaMessage));
        Bus::assertDispatched(UpdateGciCurationFromStreamMessage::class, function ($job) use ($kafkaMessage) {
            return json_encode($job->gciMessage->getPayload()) == $kafkaMessage->payload;
        });
    }
}
