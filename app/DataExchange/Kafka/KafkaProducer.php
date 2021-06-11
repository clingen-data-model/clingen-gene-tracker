<?php

namespace App\DataExchange\Kafka;

use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\Exceptions\StreamingServiceException;

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
            \Log::debug(__METHOD__);
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
            \Log::debug('topic->produce ran');
            $this->rdKafkaProducer->poll(0);
            \Log::debug('$this->rdKafkaProducer->poll(0); ran');
            
            while ($this->rdKafkaProducer->getOutQLen() > 0) {
                $this->rdKafkaProducer->poll(50);
                \Log::debug('polling b/c $this->rdKafkaProducer->getOutQLen(): '.$this->rdKafkaProducer->getOutQLen());
            }
            \Log::debug('q len is 0.  finishing up.');
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
        dump("pushsing $message on ".$this->topic->getName());
        if (!$this->topic) {
            throw new StreamingServiceException('You must set a topic on the Producer before you can use KafkaProducer::produce');
        }
        $this->produceOnTopic($message, $this->topic);
    }
}
