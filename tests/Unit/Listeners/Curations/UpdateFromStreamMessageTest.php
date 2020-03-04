<?php

namespace Tests\Unit\Listeners\Curations;

use Tests\TestCase;
use App\StreamError;
use App\Events\StreamMessages\Received;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group gci
 */
class UpdateFromStreamMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function setup():void
    {
        parent::setup();
        $this->createMsgPath = base_path('tests/files/gci_messages/created_message.json');
    }
    

    /**
     * @test
     */
    public function adds_to_missing_list_if_no_match_on_create_message()
    {
        $kafkaMessage = $this->makeMessage(file_get_contents($this->createMsgPath));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('stream_errors', [
            'type' => 'unmatchable curation',
            'direction' => 'incoming',
            // 'message_payload' => json_encode(json_decode($kafkaMessage->payload)),
            'notification_sent_at' => null
        ]);

        $streamError = StreamError::query()->first();
        $this->assertEquals($streamError->message_payload->report_id, json_decode($kafkaMessage->payload)->report_id);
    }

    private function makeMessage($payload)
    {
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->payload = $payload;

        return $kafkaMessage;
    }
}
