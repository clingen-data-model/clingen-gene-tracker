<?php

namespace App\DataExchange\Kafka;

use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\Exceptions\StreamingServiceException;
use Ramsey\Uuid\Uuid;

class KafkaProducer implements MessagePusher
{
    protected $rdKafkaProducer;
    protected $topic;
    protected $kafkaConfig;
 
    public function __construct(\RdKafka\Producer $phpRdProducer)
    {
        $this->rdKafkaProducer = $phpRdProducer;
        // $this->rdKafkaProducer->setLogLevel(LOG_DEBUG);
    }

    private function produceOnTopic($message, \RdKafka\ProducerTopic $topic)
    {
        try {
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message, Uuid::uuid4()->toString());
            $this->rdKafkaProducer->poll(0);
            
            while ($this->rdKafkaProducer->getOutQLen() > 0) {
                $this->rdKafkaProducer->poll(50);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function topic(string $topic)
    {
        if ($this->topic) {
            return $this->topic;
        }
        $this->topic = $this->rdKafkaProducer->newTopic($topic);
        return $this;
    }

    public function push(string $message)
    {
        if (!$this->topic) {
            throw new StreamingServiceException('You must set a topic on the Producer before you can use KafkaProducer::produce');
        }
        $this->produceOnTopic($message, $this->topic);
    }
}
