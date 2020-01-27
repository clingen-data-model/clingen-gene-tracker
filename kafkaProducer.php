<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

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
$conf->set('security.protocol', 'ssl');
$conf->set('metadata.broker.list', 'exchange.clinicalgenome.org:9093');
$conf->set('ssl.certificate.location', $sslCertLocation);
$conf->set('ssl.key.location', $sslKeyLocation);
$conf->set('ssl.ca.location', $sslCaLocation);
if ($sslKeyPassword) {
    $conf->set('ssl.key.password', $sslKeyPassword);
}


$rk = new RdKafka\Producer($conf);
$rk->setLogLevel(LOG_DEBUG);
// $rk->addBrokers("127.0.0.1");

$topic = $rk->newTopic("test");

$stdin = fopen("php://stdin", "r");

echo "starting input loop...\n";
while (true) {
    $line = trim(fgets($stdin));
    if (in_array($line, ['quit', 'exit'])) {
        break;
    }
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, trim($line));
    $rk->poll(0);
}


while ($rk->getOutQLen() > 0) {
    $rk->poll(50);
}
