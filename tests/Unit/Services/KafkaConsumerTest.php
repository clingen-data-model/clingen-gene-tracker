<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\KafkaConsumer;
use App\Contracts\MessageConsumer;
use App\Events\StreamMessages\Received;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group streaming-service
 * @group kafka
 */
class KafkaConsumerTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();
        if (!class_exists(\RdKafka\KafkaConsumer::class)) {
            $this->markTestSkipped('RdKafka is not installed so skip these tests');
        }
    }

    /**
     * @test
     */
    public function implements_MessageConsumer()
    {
        $consumer = app()->make(KafkaConsumer::class);

        $this->assertInstanceOf(MessageConsumer::class, $consumer);
    }

    /**
     * @test
     */
    public function can_get_a_list_of_available_topics()
    {
        // Mock topics to be returned
        $topicA = \Mockery::mock(\RdKafka\Metadata\Topic::class);
        $topicA->shouldReceive('getName')->andReturn('test_a');
        $topicA->shouldReceive('getOffset')->andReturn(0);

        $topicB = \Mockery::mock(\RdKafka\Metadata\Topic::class);
        $topicB->shouldReceive('getName')->andReturn('test_b');
        $topicB->shouldReceive('getOffset')->andReturn(10);

        // Mock Metadata object to be returned
        $mkRdMetadata = \Mockery::mock(\RdKafka\Metadata::class);
        $mkRdMetadata->shouldReceive('getTopics')
            ->andReturn([$topicA, $topicB]);

        // Mock Consumer that makes final call
        $mkRdConsumer = \Mockery::mock(\RdKafka\KafkaConsumer::class);
        $mkRdConsumer->shouldReceive('getMetadata')
            ->andReturn($mkRdMetadata);

        $appConsumer = new KafkaConsumer($mkRdConsumer, app()->make(Dispatcher::class));

        $expected = [
            ['name' => 'test_a', 'offset' => 0],
            ['name' => 'test_b', 'offset' => 10],
        ];
        $this->assertEquals($expected, $appConsumer->listTopics());
    }

    /**
     * @test
     */
    public function can_add_topics_to_listen_to_once()
    {
        $consumer = app()->make(KafkaConsumer::class);

        $consumer->addTopic('test');
        $consumer->addTopic('test');
        $consumer->addTopic('beans');

        $this->assertEquals(['test', 'beans'], $consumer->topics);
    }
    
    /**
     * @test
     */
    public function can_remove_topics_to_listen_to()
    {
        $consumer = app()->make(KafkaConsumer::class);

        $consumer->addTopic('test');
        $consumer->addTopic('monkeys');
        $consumer->addTopic('beans');

        $consumer->removeTopic('test');

        $this->assertEquals(['monkeys', 'beans'], $consumer->topics);
    }

    /**
     * @test
     */
    public function dispatches_StreamMessages_Received_event_when_message_received_without_error()
    {
        $message = new \RdKafka\Message();
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->payload = 'monkeys';

        $message2 = new \RdKafka\Message();
        $message2->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message2->payload = 'Beans';

        $eofMessage = new \RdKafka\Message();
        $eofMessage->err = RD_KAFKA_RESP_ERR__PARTITION_EOF;

        // Mock Consumer that makes final call
        $mkRdConsumer = \Mockery::mock(\RdKafka\KafkaConsumer::class);
        $mkRdConsumer->shouldReceive('subscribe');
        $mkRdConsumer->shouldReceive('consume')
            ->andReturn($message, $message2, $eofMessage);

        \Event::fake([Received::class]);

        $appConsumer = new KafkaConsumer($mkRdConsumer, app()->make(Dispatcher::class));
        $appConsumer->addTopic('test');

        $appConsumer->listen();
        // $appConsumer->stopListening();
        \Event::assertDispatched(Received::class, function ($event) use ($message) {
            return $event->message == $message;
        });

        \Event::assertDispatched(Received::class, function ($event) use ($message2) {
            return $event->message == $message2;
        });
    }
}
