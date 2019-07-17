<?php
$sslCertLocation = '/etc/pki/tls/certs/kafka.web3demo.signed.crt';
$sslKeyLocation = '/etc/pki/tls/private/kafka.apache.key';
$sslCaLocation = '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert';
$group = 'unc_demo';
// $sslCertLocation = '/Users/jward3/certificates/tjward_cert_signed.crt';
// $sslKeyLocation =  '/Users/jward3/certificates/kafka.key';
// $sslCaLocation =   '/Users/jward3/certificates/ca-cert';
// $sslKeyPassword = 'test';
// $group = 'tjward_unc';

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
// $conf->set('group.id', $group);

// Initial list of Kafka brokers
$conf->set('security.protocol', 'ssl');
$conf->set('metadata.broker.list', 'exchange.clinicalgenome.org:9093');
$conf->set('ssl.certificate.location', $sslCertLocation);
$conf->set('ssl.key.location', $sslKeyLocation);
$conf->set('ssl.ca.location', $sslCaLocation);
$conf->set('ssl.key.password', $sslKeyPassword);

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
