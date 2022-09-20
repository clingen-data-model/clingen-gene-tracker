<?php

namespace App\DataExchange\Kafka;

use \RdKafka\Conf;

use Illuminate\Support\Facades\Log;
use App\DataExchange\Exceptions\StreamingServiceException;
use App\DataExchange\Kafka\KafkaEnvValidator;

class KafkaConfig
{
    private $conf;

    public function __construct(KafkaEnvValidator $validator)
    {
        $validator();
        $this->conf = new Conf();

        // Initial list of Kafka brokers
        $this->setBrokers()
            ->setLogLevel();

        // security config
        if (!app()->environment('testing')) {
            $this->configSecurity();
        }
        
        $this->setErrorCallback()
            ->setStatsCallback();
    }

    public function set ($key, $value): self
    {
        $this->conf->set($key, $value);
        return $this;
    }

    public function setGroup($group = null)
    {
        $group = $group ? $group : config('dx.group', 'unc_staging');
        return $this->set('group.id', $group);
    }

    public function setRebalanceCallback(Callable $callback)
    {
        $this->conf->setRebalanceCb($callback);
        return $this;
    }

    public function setBrokers(): self
    {
        $this->conf->set('metadata.broker.list', config("dx.broker"));
        return $this;
    }

    public function setLogLevel($logLevel = null): self
    {
        $logLevel = $logLevel ?? LOG_DEBUG;
        $this->conf->set('log_level', (string) $logLevel);
        return $this;
    }
    
    public function setErrorCallback(?Callable $callback = null): self
    {
        $callback = $callback ?? 
            function ($kafka, $err, $reason) {
                throw new StreamingServiceException("Kafka producer error: ".rd_kafka_err2str($err)." (reason: ".$reason.')');
            };
        $this->conf->setErrorCb($callback);
        return $this;
    }

    public function setStatsCallback(?Callable $callback = null): self
    {
        $callback = $callback ??
                    function ($kafka, $json, $json_len) {
                        Log::info('Kafka Stats ', json_decode($json));
                    }; 
        $this->conf->setStatsCb($callback);
        return $this;
    }
    
    public function setDeliveryReportCallback(?Callable $callback = null): self
    {
        $callback = $callback ?? 
                    function ($kafka, $message) {
                        if ($message->err) {
                            throw new StreamingServiceException('Delivery Report Message: '.rd_kafka_err2str($message->err));
                        }
                    };
        $this->conf->setDrMsgCb($callback);
        return $this;
    }

    public function setConsumeCallback(Callable $callback): self
    {
        $this->conf->setConsumeCb($callback);
        return $this;
    }

    public function setOffsetCommitCb(Callable $callable): self
    {
        $this->conf->setOffsetCommitCb($callable);
        return $this;
    }
    
    

    public function getConfig()
    {
        return $this->conf;
    }

    private function configSecurity(): self
    {
        $this->conf->set('security.protocol', 'sasl_ssl');
        $this->conf->set('sasl.mechanism', 'PLAIN');
        $this->conf->set('sasl.username', config('dx.username'));
        $this->conf->set('sasl.password', config('dx.password'));

        return $this;
    }
}
