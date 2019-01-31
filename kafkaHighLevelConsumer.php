<?php

$sslCertLocation = '/etc/pki/tls/certs/kafka.web3demo.signed.crt';
$sslKeyLocation = '/etc/pki/tls/private/kafka.apache.key';
$sslCaLocation = '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert';


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
$conf->set('group.id', 'unc_demo');

// Initial list of Kafka brokers
$conf->set('security.protocol', 'ssl');
$conf->set('metadata.broker.list', 'exchange.clinicalgenome.org:9093');
$conf->set('ssl.certificate.location', $sslCertLocation);
$conf->set('ssl.key.location', $sslKeyLocation);
$conf->set('ssl.ca.location', $sslCaLocation);

$topicConf = new RdKafka\TopicConf();

// Set where to start consuming messages when there is no initial offset in
// offset store or the desired offset is out of range.
// 'smallest': start from the beginning
$topicConf->set('auto.offset.reset', 'smallest');

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
            var_dump($message);
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "No more messages; will wait for more\n";
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "Timed out\n";
            echo "Timed out\n";
            break;
        case RD_KAFKA_RESP_ERR__FAIL:
            echo "Failed to communicate with broker\n";
            break;
        case RD_KAFKA_RESP_ERR__BAD_MSG:
                echo "Bad message format\n";
                break;
        case RD_KAFKA_RESP_ERR__RESOLVE:
                echo "Host resolution filure";
                break;
        case RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC:
                echo "unkown topic\n";
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
