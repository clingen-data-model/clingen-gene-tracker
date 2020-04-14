<?php

namespace App\Console\Commands;

use App\Services\KafkaConfig;
use Illuminate\Console\Command;
use \RdKafka\KafkaConsumer as RdKafkaConsumer;
use App\Jobs\StreamingService\UpdateTopicPartitionOffset;

class StreamingServiceSetTopicOffset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'streaming-service:set-offset {topic : Name of topic} {offset : Numeric offset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the offset for the topic';

    private $offsetSet = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $conf = (new KafkaConfig())
                 ->setRebalanceCallback(function (RdKafkaConsumer $consumer, $err, array $topicPartitions = null) {
                     dump('rebalanceCallback...');
                     if ($err == RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
                         $this->info("Assigned topicPartions...");
                         $consumer->assign($topicPartitions);

                         foreach ($topicPartitions as $tp) {
                             $this->info("Setting offset for topic ".$tp->getTopic()." to ".$this->argument('offset'));
                             $tp->setOffset($this->argument('offset'));
                             $consumer->commit([$tp]);
                         }
                         return;
                     }

                     if ($err == RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
                         $consumer->assign(null);
                         return;
                     }

                     throw new \Exception($err);
                 })->getConfig();
        
        $consumer = new RdKafkaConsumer($conf);
        $consumer->subscribe([$this->argument('topic')]);

        while (true) {
            $message = $consumer->consume(10000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    echo $message->offset.': '.$message->payload."\n";
                    $tp = new \RdKafka\TopicPartition($this->argument('topic'), 0);
                    $setOffset = max($message->offset - 1, 1);
                    $tp->setOffset($setOffset);
                    $consumer->commit([$tp]);
                    UpdateTopicPartitionOffset::dispatch($message->topic_name, $message->partition, $setOffset);
                    // Set the offset again since we have to consume in order to get anything to work
                    break 2;
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "\n\n**No more messages; will wait for more...\n\n";
                    break 2;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "**Timed out\n";
                    break;
                case RD_KAFKA_RESP_ERR__FAIL:
                    echo "**Failed to communicate with broker\n";
                    break;
                case RD_KAFKA_RESP_ERR__BAD_MSG:
                    echo "**Bad message format\n";
                    break;
                case RD_KAFKA_RESP_ERR__RESOLVE:
                    echo "**Host resolution failure";
                    break;
                case RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC:
                    echo "**unknown topic\n";
                    break;
                case RD_KAFKA_RESP_ERR_INVALID_GROUP_ID:
                    echo "**invalid group id\n";
                    break;
                case RD_KAFKA_RESP_ERR_GROUP_AUTHORIZATION_FAILED:
                    echo "**group auth failed\n";
                    break;
                default:
                    echo "**Unknown Error: ".$message->err."\n";
                    break;
        
            }
        }
    }
}
