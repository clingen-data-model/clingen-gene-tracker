<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

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

// Configure the group.id. All consumer with the same group.id will consume
// different partitions.
// $conf->set('group.id', $group);

// Initial list of Kafka brokers
$conf->set('security.protocol', 'sasl_ssl');
$conf->set('sasl.mechanism', 'PLAIN');
$conf->set('sasl.username', env('DX_USERNAME'));
$conf->set('sasl.password', env('DX_PASSWORD'));
$conf->set('group.id', env('DX_GROUP'));
// $conf->set('metadata.broker.list', env('DX_BROKER'));


$rk = new RdKafka\Producer($conf);
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers(env('DX_BROKER'));

$topic = $rk->newTopic("gt-precuration-events-test");

$stdin = fopen("php://stdin", "r");

echo "starting input loop...\n";
while (true) {
    $line = trim(fgets($stdin));
    if (in_array($line, ['quit', 'exit'])) {
        break;
    }
    echo "tried to produce message '$line'\n";
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, trim($line));
    $rk->poll(0);
}


while ($rk->getOutQLen() > 0) {
    $rk->poll(50);
}
