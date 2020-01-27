<?php

namespace App\Services;

use App\Exceptions\StreamingServiceException;

class KafkaConfig
{
    public function __construct()
    {
    }

    public function __invoke()
    {
        $conf = new \RdKafka\Conf();
        $conf->set('group.id', config('streaming-service.group', 'unc_demo'));
        
        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', 'exchange.clinicalgenome.org:9093');

        // security config
        $conf->set('security.protocol', 'ssl');
        $conf->set('ssl.certificate.location', config('streaming-service.cert-location', '/etc/pki/tls/certs/kafka.web3demo.signed.crt'));
        $conf->set('ssl.key.location', config('streaming-service.key-location', '/etc/pki/tls/private/kafka.apache.key'));
        $conf->set('ssl.ca.location', config('streaming-service.ca-location', '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert'));

        if (config('streaming-service.ssl-key-password', null)) {
            $conf->set('ssl.key.password', config('streaming-service.ssl-key-password', null));
        }

        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
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
                throw new StreamingServiceException('DrMsg: '.rd_kafka_err2str($message->err));
            }
        });

        return $conf;
    }
}
