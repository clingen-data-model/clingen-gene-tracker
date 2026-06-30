<?php

namespace Tests\Feature\DataExchange;

use Mockery;
use Tests\TestCase;
use App\IncomingStreamMessage;
use App\DataExchange\Contracts\MessageConsumer;
use App\DataExchange\Actions\ConsumeGpmEvents;
use App\DataExchange\Actions\GcepFinalApprovalHandler;

class ConsumeGpmEventsTest extends TestCase
{
    public function test_it_stores_and_routes_gcep_final_approval(): void
    {
        config([
            'dx.topics.incoming.gpm-general-events' => 'gpm-general-events',
            'dx.gpm_event_handlers' => [ 'gcep_final_approval' => GcepFinalApprovalHandler::class ],
        ]);

        $payload = [
            'event_type' => 'gcep_final_approval',
            'schema_version' => '2.0.1',
            'data' => [
                'group' => [
                    'type' => 'gcep',
                    'expert_panel' => ['affiliation_id' => '40073', 'type' => 'gcep'],
                ],
                'members' => [],
                'scope' => [
                    'genes' => [],
                ],
            ],
        ];

        $message = $this->makeKafkaMessage(payload: $payload, partition: 0, offset: 101);
        $handler = Mockery::mock(GcepFinalApprovalHandler::class);
        $handler->shouldReceive('handle')->once()->with(Mockery::on(function ($incomingMessage) {
                    return $incomingMessage instanceof IncomingStreamMessage && $incomingMessage->topic === 'gpm-general-events' && $incomingMessage->partition === 0 && $incomingMessage->offset === 101;
                }),
                $payload
            );

        $this->instance(GcepFinalApprovalHandler::class, $handler);
        $this->bindConsumerWithMessage($message);
        app(ConsumeGpmEvents::class)->handle(1);
        $this->assertDatabaseHas('incoming_stream_messages', [
                'topic' => 'gpm-general-events',
                'partition' => 0,
                'offset' => 101,
            ]
        );
    }

    public function test_it_ignores_unsupported_gpm_events_before_storing(): void
    {
        config([
            'dx.topics.incoming.gpm-general-events' => 'gpm-general-events',
            'dx.gpm_event_handlers' => [
                'gcep_final_approval' => GcepFinalApprovalHandler::class,
            ],
        ]);

        $payload = [
            'event_type' => 'some_unrelated_gpm_event',
            'data' => [],
        ];

        $message = $this->makeKafkaMessage(payload: $payload, partition: 0, offset: 102);
        $handler = Mockery::mock(GcepFinalApprovalHandler::class);
        $handler->shouldNotReceive('handle');
        $this->instance(GcepFinalApprovalHandler::class, $handler);
        $this->bindConsumerWithMessage($message);
        app(ConsumeGpmEvents::class)->handle(1);
        $this->assertDatabaseMissing('incoming_stream_messages', [
                'topic' => 'gpm-general-events',
                'partition' => 0,
                'offset' => 102,
            ]
        );
    }

    private function makeKafkaMessage(array $payload, int $partition, int $offset): \RdKafka\Message {
        $message = Mockery::mock(\RdKafka\Message::class);

        $message->payload = json_encode($payload);
        $message->topic_name = 'gpm-general-events';
        $message->partition = $partition;
        $message->offset = $offset;
        $message->timestamp = now()->timestamp;
        $message->err = 0;
        $message->key = null;

        return $message;
    }

    private function bindConsumerWithMessage(\RdKafka\Message $message): void 
    {
        $consumer = Mockery::mock(MessageConsumer::class);
        $consumer->shouldReceive('addTopic')->once()->with('gpm-general-events')->andReturnSelf();
        $consumer->shouldReceive('consumeSomeMessages')->once()->with(1, Mockery::type('callable'))
                ->andReturnUsing(function ($limit, $callback) use ($message, $consumer) {
                    $callback($message);
                    return $consumer;
                });
        $this->instance(MessageConsumer::class, $consumer);
    }

    public function test_it_continues_when_a_supported_handler_fails(): void
    {
        config([
            'dx.topics.incoming.gpm-general-events' => 'gpm-general-events',
            'dx.gpm_event_handlers' => [ 'gcep_final_approval' => GcepFinalApprovalHandler::class ],
        ]);
        $payload = [
            'event_type' => 'gcep_final_approval',
            'data' => [
                'group' => [
                    'type' => 'gcep',
                    'expert_panel' => ['affiliation_id' => '40073', 'type' => 'gcep'],
                ],
            ],
        ];
        $message = $this->makeKafkaMessage(payload: $payload, partition: 0, offset: 103);
        $handler = Mockery::mock(GcepFinalApprovalHandler::class);
        $handler->shouldReceive('handle')->once()->andThrow(new \RuntimeException('Test handler failure'));
        $this->instance(GcepFinalApprovalHandler::class, $handler);
        $this->bindConsumerWithMessage($message);
        app(ConsumeGpmEvents::class)->handle(1);
        $this->assertDatabaseHas('incoming_stream_messages', [
                'topic' => 'gpm-general-events',
                'partition' => 0,
                'offset' => 103,
            ]
        );
    }

    public function test_it_ignores_non_gcep_events_before_storing(): void
    {
        config([
            'dx.topics.incoming.gpm-general-events' => 'gpm-general-events',
            'dx.gpm_event_handlers' => [
                'genes_added' => GcepFinalApprovalHandler::class,
            ],
        ]);

        $payload = [
            'event_type' => 'genes_added',
            'data' => [
                'group' => [
                    'type' => 'vcep',
                    'expert_panel' => [
                        'type' => 'vcep',
                        'affiliation_id' => '50031',
                    ],
                ],
                'genes' => [
                    [
                        'hgnc_id' => 10483,
                        'gene_symbol' => 'RYR1',
                    ],
                ],
            ],
        ];

        $message = $this->makeKafkaMessage($payload, 0, 104);
        $handler = Mockery::mock(GcepFinalApprovalHandler::class);
        $handler->shouldNotReceive('handle');
        $this->instance(GcepFinalApprovalHandler::class, $handler);
        $this->bindConsumerWithMessage($message);
        app(ConsumeGpmEvents::class)->handle(1);
        $this->assertDatabaseMissing('incoming_stream_messages', ['topic' => 'gpm-general-events', 'partition' => 0, 'offset' => 104]);
    }
}