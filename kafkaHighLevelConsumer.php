<?php

use App\Exceptions\StreamingServiceException;

require __DIR__ . '/vendor/autoload.php';


function commitOffset($consumer, $topicPartition, $offset, $attempt = 0)
{
    if ($offset >= 0) {
        echo "Committing offset set to $offset for topic ".$topicPartition->getTopic()." on partition ".$topicPartition->getPartition()."...\n";
    } else {
        echo "Don't update offset.\n";
        return;
    }
    // $topicPartition = new RdKafka\TopicPartition($topic, 0, $offset);
    $topicPartition->setOffset($offset);
    $consumer->commit([$topicPartition]);
    // $offsetMessage = new RdKafka\Message();
    // $offsetMessage->partition = $topicPartition->partition;
    // $offsetMessage->topic_name = $topicPartition->topic;
    // $offsetMessage->offset = $offset;
    // $consumer->commit($offsetMessage);
}

$topics = isset($argv[1]) ? explode(',', $argv[1]) : ['test'];
$offset = (int)(isset($argv[2]) ? $argv[2] : -1);
$dotenv = Dotenv\Dotenv::create(__DIR__);

$dotenv->load();

$sslCertLocation = env('KAFKA_CERT', '/etc/pki/tls/certs/kafka.web3demo.signed.crt');
$sslKeyLocation =  env('KAFKA_KEY_LOCATION', '/etc/pki/tls/private/kafka.apache.key');
$sslCaLocation =   env('KAFKA_CA_LOCATION', '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert');
$sslKeyPassword = env('KAFKA_KEY_PASSWORD', null);
$group = env('KAFKA_GROUP', 'unc_demo');

$conf = new RdKafka\Conf();

// Configure the group.id. All consumer with the same group.id will consume
// different partitions.
$conf->setErrorCb(function ($kafka, $err, $reason) {
    throw new StreamingServiceException("Kafka producer error: ".rd_kafka_err2str($err)." (reason: ".$reason.')');
});

$conf->setStatsCb(function ($kafka, $json, $json_len) {
    Log::info('Kafka Stats ', json_decode($json));
});

$conf->setDrMsgCb(function ($kafka, $message) {
    if ($message->err) {
        throw new StreamingServiceException('DrMsg: '.rd_kafka_err2str($err));
    }
});
$conf->set('group.id', $group);

echo "setting group to $group...\n";

// Initial list of Kafka brokers
$conf->set('security.protocol', 'ssl');
$conf->set('metadata.broker.list', 'exchange.clinicalgenome.org:9093');
$conf->set('ssl.certificate.location', $sslCertLocation);
$conf->set('ssl.key.location', $sslKeyLocation);
$conf->set('ssl.ca.location', $sslCaLocation);

if ($sslKeyPassword) {
    $conf->set('ssl.key.password', $sslKeyPassword);
}

$topicConf = new RdKafka\TopicConf();

// Set where to start consuming messages when there is no initial offset in
// offset store or the desired offset is out of range.
// 'smallest': start from the beginning
$topicConf->set('auto.offset.reset', 'beginning');
$conf->set('auto.offset.reset', 'beginning');

// Set a rebalance callback to log partition assignments (optional)
$conf->setRebalanceCb(function (RdKafka\KafkaConsumer $consumer, $err, array $topicPartitions = null) use ($offset) {
    switch ($err) {
        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
            echo "Assign partions...";
            $consumer->assign($topicPartitions);
            
            foreach ($topicPartitions as $tp) {
                commitOffset($consumer, $tp, $offset);
            }

        break;

         case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
            echo "\nCommittedOffsets\n";
            $assignments = $consumer->getAssignment();
            var_dump($assignments);
            var_dump($consumer->getCommittedOffsets($assignments, 10000000));

            echo "\n...Assigning to null...\n";
             $consumer->assign(null);
             break;

         default:
            throw new \Exception($err);
    }
});

// Set the configuration to use for subscribed/assigned topics
// $conf->setDefaultTopicConf($topicConf);

$consumer = new RdKafka\KafkaConsumer($conf);

// echo "getting Available Topics...\n";
// $availableTopics = $consumer->getMetadata(true, null, 60e3)->getTopics();
// echo "Available Topics: \n";
// foreach ($availableTopics as $avlTopic) {
//     echo "  ".$avlTopic->getTopic()."\n";
// }

// Subscribe to topic 'test'
echo "Subscribing to the following topics:\n".implode("\n  ", $topics)."...\n";
$consumer->subscribe($topics);
var_dump($consumer->getAssignment());
echo "\nWaiting for partition assignment...\n";

$count = 0;
while (true) {
    $message = $consumer->consume(10000);
    // dump($message);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            echo $message->payload."\n";
            $count++;
            if ($count > 2) {
                break 2;
            }
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "\n\nNo more messages; will wait for more...\n\n";
            break;
            // echo "\n\nFound all messages. Closing for now.\n\n";
            // break 2;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "Timed out\n";
            // echo "Timed out\n";
            break;
        case RD_KAFKA_RESP_ERR__FAIL:
            echo "Failed to communicate with broker\n";
            break;
        case RD_KAFKA_RESP_ERR__BAD_MSG:
            echo "Bad message format\n";
            break;
        case RD_KAFKA_RESP_ERR__RESOLVE:
            echo "Host resolution failure";
            break;
        case RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC:
            echo "unknown topic\n";
            break;
        case RD_KAFKA_RESP_ERR_INVALID_GROUP_ID:
            echo "invalid group id\n";
            break;
        case RD_KAFKA_RESP_ERR_GROUP_AUTHORIZATION_FAILED:
            echo "group auth failed\n";
            break;
        default:
            echo "Unknown Error: ".$message->err."\n";
            break;

    }
}
