<?php

namespace Tests\Feature\DataExchange;

use Mockery;
use Tests\TestCase;
use App\IncomingStreamMessage;
use App\DataExchange\Actions\StoreGpmIncomingMessage;

class StoreGpmIncomingMessageTest extends TestCase
{
    public function test_it_stores_a_gpm_message_only_once(): void
    {
        $payload = [
            'event_type' => 'gcep_final_approval',
            'schema_version' => '2.0.1',
            'data' => [
                'group' => [
                    'expert_panel' => [
                        'affiliation_id' => '40073',
                    ],
                ],
            ],
        ];

        $message = Mockery::mock(\RdKafka\Message::class);
        $message->topic_name = 'gpm-general-events';
        $message->partition = 0;
        $message->offset = 123;
        $message->timestamp = now()->timestamp;
        $message->err = 0;
        $message->key = 'gcep-40073';

        $action = app(StoreGpmIncomingMessage::class);
        $first = $action->handle($message, $payload);
        $second = $action->handle($message, $payload);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseHas('incoming_stream_messages', [
            'id' => $first->id,
            'topic' => 'gpm-general-events',
            'partition' => 0,
            'offset' => 123,
            'key' => 'gcep-40073',
            'error_code' => 0,
        ]);

        $this->assertSame(1, IncomingStreamMessage::where([
                'topic' => 'gpm-general-events',
                'partition' => 0,
                'offset' => 123,
            ])->count()
        );
    }

    public function test_it_generates_a_key_when_kafka_key_is_missing(): void
    {
        $payload = ['event_type' => 'gcep_final_approval'];
        $message = Mockery::mock(\RdKafka\Message::class);
        $message->topic_name = 'gpm-general-events';
        $message->partition = 2;
        $message->offset = 456;
        $message->timestamp = now()->timestamp;
        $message->err = 0;
        $message->key = null;

        $storedMessage = app(StoreGpmIncomingMessage::class)->handle($message, $payload);
        $this->assertSame('gpm-general-events:2:456', $storedMessage->key);
    }
}