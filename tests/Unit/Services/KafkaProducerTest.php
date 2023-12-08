<?php

namespace Tests\Unit\Services;

use App\DataExchange\Exceptions\StreamingServiceException;
use App\DataExchange\Kafka\KafkaProducer;
use  Mockery;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * @group streaming-service
 * @group kafka
 */
class KafkaProducerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(\RdKafka\Producer::class)) {
            $this->markTestSkipped('RdKafka is not installed so skip these tests');
        }
    }

    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $producer = app()->make(KafkaProducer::class);
        // $producer = new KafkaProducer(app()->make(\RdKafka\Producer::class));
        $this->assertInstanceOf(KafkaProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_pushes_messages_to_a_topic()
    {
        $message = 'test message';

        $producer = app()->make(KafkaProducer::class);
        $topic = Mockery::mock(\RdKafka\ProducerTopic::class);
        $uuid = Uuid::uuid4()->toString();
        $topic->shouldReceive('produce')->with(RD_KAFKA_PARTITION_UA, 0, $message, $uuid)->once();

        $this->invokeMethod($producer, 'produceOnTopic', [$message, $topic, $uuid]);
        // $producer->produceOnTopic($message, $topic);
    }

    /**
     * @test
     */
    public function produce_throws_exception_when_topic_not_set()
    {
        $producer = app()->make(KafkaProducer::class);

        $this->expectException(StreamingServiceException::class);

        $producer->push('test');
    }
}
