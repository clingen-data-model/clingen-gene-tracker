<?php

namespace App\Services\Kafka;

use Illuminate\Support\Facades\Log;
use App\Exceptions\StreamingServiceException;

class KafkaConfig
{
    private $conf;

    public function __construct($group = null)
    {
        $this->conf = new \RdKafka\Conf();
        $this->setGroup($group);

        // Initial list of Kafka brokers
        $this->conf->set('metadata.broker.list', config("streaming-service.broker"));
        $this->conf->set('log_level', (string) LOG_DEBUG);

        // security config
        $this->configureAuth();
        
        $this->conf->setErrorCb(function ($kafka, $err, $reason) {
            throw new StreamingServiceException("Kafka producer error: ".rd_kafka_err2str($err)." (reason: ".$reason.')');
        });

        $this->conf->setStatsCb(function ($kafka, $json, $json_len) {
            Log::info('Kafka Stats ', json_decode($json));
        });

        $this->conf->setDrMsgCb(function ($kafka, $message) {
            if ($message->err) {
                throw new StreamingServiceException('DrMsg: '.rd_kafka_err2str($message->err));
            }
        });
    }
    public function setGroup($group = null)
    {
        $group = $group ? $group : config('streaming-service.kafka_group', 'unc_demo');
        $this->conf->set('group.id', $group);
    }

    public function setRebalanceCallback($callback)
    {
        $this->conf->setRebalanceCb($callback);
        return $this;
    }

    public function getConfig()
    {
        return $this->conf;
    }

    private function configureAuth()
    {
        if (app()->environment('testing')) {
            return;
        }

        if (config('streaming-service.broker') == 'exchange.clinicalgenome.org:9093') {
            $this->conf->set('security.protocol', 'ssl');
            $this->conf->set('ssl.certificate.location', config('streaming-service.cert-location'));
            $this->conf->set('ssl.key.location', config('streaming-service.key-location'));
            $this->conf->set('ssl.ca.location', config('streaming-service.ca-location', '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert'));
    
            if (config('streaming-service.ssl-key-password', null)) {
                $this->conf->set('ssl.key.password', config('streaming-service.ssl-key-password', null));
            }

            return;
        }

        $this->conf->set('security.protocol', 'sasl_ssl');
        $this->conf->set('sasl.mechanism', 'PLAIN');
        $this->conf->set('sasl.username', config('streaming-service.kafka_username'));
        $this->conf->set('sasl.password', config('streaming-service.kafka_password'));
    }
    
}
