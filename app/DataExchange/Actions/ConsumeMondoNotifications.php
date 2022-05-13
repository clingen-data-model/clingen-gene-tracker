<?php

namespace App\DataExchange\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\DataExchange\Contracts\MessageConsumer;
use App\DataExchange\Kafka\KafkaConfig;
use App\DataExchange\Kafka\KafkaConsumer;
use Illuminate\Events\Dispatcher;

class ConsumeMondoNotifications
{
    use AsCommand;

    public $commandSignature = 'dx:consume-mondo {--reset-offset : Set topic offset to 0 } {--limit= : Limit the number of messages to read from the topic at one time.} {--dry-run : Consume the messages but do not send notifications; reset to 0 when finished.}';

    public function __construct(
        private NotifyMondoObsoletionCandidate $notifyCandidateAction,
        private MessageConsumer $consumer
    )
    {
        
    }
    

    public function handle($limit = null): void
    {
        $this->consumeMessages(function ($message) {
            $payload = json_decode($message->payload);
            if ($payload->event_type == 'obsoletion_candidate') {
                $this->notifyCandidateAction->handle($payload);
            }
        }, $limit);
    }

    public function asCommand(Command $command): void
    {
        $limit = $command->option('limit') ?? null;
        $this->handle($limit);
    }

    private function consumeMessages(callable $callback, $limit = null): void
    {
        // $consumer = $this->getConsumer();
        $this->consumer->addTopic(config('dx.topics.incoming.mondo-notifications'));

        if ($limit) {
            $this->consumer->consumeSomeMessages($limit, $callback);
            return;
        }

        $this->consumer->consume($callback);
    }
    

    private function getConsumer()
    {
        $kafkaConfig = app()->make(KafkaConfig::class);

        $kafkaConfig->setRebalanceCallback(
            function (\RdKafka\KafkaConsumer $consumer, $err, array $topicPartitions = null) {
                switch ($err) {
                    case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                        $consumer->assign($topicPartitions);
                        
                        foreach ($topicPartitions as $tp) {
                            $tp->setOffset(0);
                            $consumer->commit([$tp]);
                        }
            
                    break;
            
                     case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                        $assignments = $consumer->getAssignment();
                         $consumer->assign(null);
                         break;
            
                     default:
                        throw new \Exception($err);
                }        
            });

        $kafkaConsumer = new \RdKafka\KafkaConsumer($kafkaConfig->getConfig());
        $consumer = new KafkaConsumer($kafkaConsumer, app()->make(Dispatcher::class));

        $consumer->addTopic(config('dx.topics.incoming.mondo-notifications'));

        return $consumer;
    }
    
    
    
}