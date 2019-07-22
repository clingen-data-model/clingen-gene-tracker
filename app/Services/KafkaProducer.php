<?php

namespace App\Services;

use App\Contracts\MessagePusher;
use Illuminate\Support\Facades\Log;
use App\Exceptions\StreamingServiceException;

class KafkaProducer implements MessagePusher
{
    protected $rdKafkaProducer;
    protected $topic;
 
    public function __construct()
    {
        $this->rdKafkaProducer = new \RdKafka\Producer($this->assembleConfig());
        $this->rdKafkaProducer->setLogLevel(LOG_DEBUG);
    }

    private function produceOnTopic($message, \RdKafka\ProducerTopic $topic)
    {
        try {
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
            $this->rdKafkaProducer->poll(0);
    
            while ($this->rdKafkaProducer->getOutQLen() > 0) {
                $this->rdKafkaProducer->poll(50);
            }
        } catch (\Throwable $e) {
            report($e);
        }

    }

    public function topic(string $topic)
    {
        if ($this->topic) {
            return $this->topic;
        }
        $this->topic = $this->rdKafkaProducer->newTopic($topic);
        return $this;
    }

    public function push(string $message)
    {
        if (!$this->topic) {
            throw new StreamingServiceException('You must set a topic on the Producer before you can use KafkaProducer::produce');
        }
        $this->produceOnTopic($message, $this->topic);
    }

    private function assembleConfig()
    {
        $sslCertLocation = config('streaming-service.cert-location', '/etc/pki/tls/certs/kafka.web3demo.signed.crt');
        $sslKeyLocation = config('streaming-service.key-location', '/etc/pki/tls/private/kafka.apache.key');
        $sslCaLocation = config('streaming-service.ca-location','/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert');
        $sslKeyPassword = config('streaming-service.ssl-key-password', null);
        $group = config('streaming-service.group', 'unc_demo');

        $conf = new \RdKafka\Conf();

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

        // Initial list of Kafka brokers
        $conf->set('security.protocol', 'ssl');
        $conf->set('metadata.broker.list', 'exchange.clinicalgenome.org:9093');
        $conf->set('ssl.certificate.location', $sslCertLocation);
        $conf->set('ssl.key.location', $sslKeyLocation);
        $conf->set('ssl.ca.location', $sslCaLocation);
        // $conf->set('debug', config('app.debug') ? 'broker,topic,msg' : '');
        if ($sslKeyPassword) {
            $conf->set('ssl.key.password', $sslKeyPassword);
        }

        // dd($conf->dump());
        return $conf;
        
    }

    
}
