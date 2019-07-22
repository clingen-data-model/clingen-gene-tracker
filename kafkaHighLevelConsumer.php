<?php

use App\Exceptions\StreamingServiceException;
require __DIR__ . '/vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
// exec('source .env');

$sslCertLocation = env('KAFKA_CERT', '/etc/pki/tls/certs/kafka.web3demo.signed.crt');
$sslKeyLocation =  env('KAFKA_KEY_LOCATION', '/etc/pki/tls/private/kafka.apache.key');
$sslCaLocation =   env('KAFKA_CA_LOCATION', '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert');
$sslKeyPassword = env('KAFKA_KEY_PASSWORD', null);
$group = env('KAFKA_GROUP', 'unc_demo');

$conf = new RdKafka\Conf();

// Set a rebalance callback to log partition assignments (optional)
$conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
    switch ($err) {
        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
            echo "Assign: ";
            var_dump($partitions);
            $kafka->assign($partitions);
            break;

         case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
             echo "Revoke: ";
             var_dump($partitions);
             $kafka->assign(null);
             break;

         default:
            throw new \Exception($err);
    }
});

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

// Set the configuration to use for subscribed/assigned topics
$conf->setDefaultTopicConf($topicConf);

$consumer = new RdKafka\KafkaConsumer($conf);

// Subscribe to topic 'test'
$consumer->subscribe(['test']);

echo "Waiting for partition assignment... (make take some time when\n";
echo "quickly re-joining the group after leaving it.)\n";

while (true) {
    $message = $consumer->consume(10000);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            echo $message->payload."\n";
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "No more messages; will wait for more\n";
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            // echo "Timed out\n";
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
                echo "unkown topic\n";
                break;
        case RD_KAFKA_RESP_ERR_INVALID_GROUP_ID:
                echo "invalid group id\n";
                break;
        case RD_KAFKA_RESP_ERR_GROUP_AUTHORIZATION_FAILED:
                // echo "group auth failed\n";
                break;
        default:
                echo "Unknown Error: ".$message->err."\n";
            break;

    }
}
