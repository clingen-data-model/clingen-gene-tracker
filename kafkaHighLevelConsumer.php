<?php

use App\DataExchange\Exceptions\StreamingServiceException;

require __DIR__ . '/vendor/autoload.php';

$argments = [];
$options = [];
foreach ($argv as $idx => $arg) {
    if ($idx == 0) {
        continue;
    }
    if (substr($arg, 0, 2) == '--') {
        $name = substr($arg, 2);
        $value = true;
        if (preg_match('/=/', $name)) {
            [$name, $value] = explode('=', $name);
        }
        $options[$name] = $value;
        continue;
    }
    $arguments[] = $arg;
}

$topics = isset($arguments[0]) ? explode(',', $arguments[0]) : ['test'];
$offset = (int)(isset($arguments[1]) ? $arguments[1] : -1);
$limit = isset($options['limit']) ? (int)$options['limit'] : false;

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();


function rSortByKeys ($array) {
    $obj = false;
    if (is_object($array)) {
        $array = (array)$array;
        $obj = true;
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = rSortByKeys($value);
        }
    }
    ksort($array);
    if ($obj) {
        return (object)$array;
    }
    return $array;
}


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
}

function configure($offset)
{
    
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
            throw new StreamingServiceException('DrMsg: '.rd_kafka_err2str($message->err));
        }
    });

    // Set where to start consuming messages when there is no initial offset in
    // offset store or the desired offset is out of range.
    // 'smallest': start from the beginning
    
    // $topicConf = new RdKafka\TopicConf();    
    // $topicConf->set('auto.offset.reset', 'beginning');
    // Set the configuration to use for subscribed/assigned topics
    // $conf->setDefaultTopicConf($topicConf);

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
                $assignments = $consumer->getAssignment();
                 $consumer->assign(null);
                 break;
    
             default:
                throw new \Exception($err);
        }
    });

    return $conf;

}

function configureForConfluent($offset)
{
    
    $conf = configure($offset);

    echo "setting group to ".env('KAFKA_GROUP', 'unc_staging')." for ".env('STREAMING_SERVICE_BROKER')."...\n";
    $conf->set('group.id', env('KAFKA_GROUP', 'unc_staging'));
    
    $conf->set('security.protocol', 'sasl_ssl');
    $conf->set('metadata.broker.list', env('STREAMING_SERVICE_BROKER'));
    $conf->set('sasl.mechanism', 'PLAIN');
    $conf->set('sasl.username', env('KAFKA_USERNAME'));
    $conf->set('sasl.password', env('KAFKA_PASSWORD'));    

    return $conf;
}

function configureForExchange($offset)
{
    throw new Exception("configureForExchange doesn't work b/c certs an no longer valid for group");

    $group = 'tjward_unc';
    $cert = "./kafka-auth/sha/unc.crt";
    $keyLocation = "./kafka-auth/sha/exchange.clinicalgenome.org.key";
    $caLocation = "./kafka-auth/ca-kafka-cert";

    $conf = configure($offset);
    
    $conf->set('group.id', $group);
    $conf->set('security.protocol', 'ssl');
    $conf->set('metadata.broker.list', 'exchange.clinicalgenome.org:9093');
    $conf->set('ssl.certificate.location', $cert);
    $conf->set('ssl.key.location', $keyLocation);
    $conf->set('ssl.ca.location', $caLocation);

    if ($sslKeyPassword) {
        $conf->set('ssl.key.password', $sslKeyPassword);
    }

    return $conf;

}


$conf = configureForConfluent($offset);
// $conf = configureForExchange($offset);

$consumer = new RdKafka\KafkaConsumer($conf);

if (count($topics) == 0 || array_key_exists('list-topics', $options)) {
    dump('should list topics');
    $availableTopics = $consumer->getMetadata(true, null, 60e3)->getTopics();
    echo "Available Topics: \n";
    foreach ($availableTopics as $idx => $avlTopic) {
        echo $avlTopic->getTopic()."\n";
    }

    echo "Which topic(s) do you want to join? (comma-delimit for > 1)";
    $stdin = fopen('php://stdin', 'r');
    $line = fgets($stdin);
    $topics = explode(',', trim($line));
    fclose($stdin);
}

if (array_key_exists('dont-listen', $options)) {
    exit;
}

// Subscribe to topic 'test'
echo "**Subscribing to the following topics:\n".implode("\n  ", $topics)."...\n";
$consumer->subscribe($topics);
var_dump($consumer->getAssignment());
echo "\nWaiting for partition assignment...\n";

$count = 0;
$keys = [];
while (true) {
    $message = $consumer->consume(10000);
    // dump($message);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            $payload = rSortByKeys(json_decode($message->payload));

            $a = json_decode($message->payload, true);

            $filePath = __DIR__.'/gene_validity_raw/'.$message->key.'.json';
            file_put_contents($filePath, $message->payload);
            // echo (json_encode([
            //     'len' => $message->len,
            //     'topic_name' => $message->topic_name,
            //     'timestamp' => $message->timestamp,
            //     'partition' => $message->partition,
            //     'payload' => json_encode($payload),
            //     'key' => $message->key,
            //     'offset' => $message->offset,
            // ], JSON_PRETTY_PRINT));
            if (!isset($keys[$message->key])) {
                $keys[$message->key] = [];
            }
            // $keys[$message->key][] = json_encode($payload, JSON_PRETTY_PRINT);
            $count++;
            if ($limit && $count > $limit) {
                break 2;
            }
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "\n\n**No more messages; will wait for more...\n\n";
            // echo "\n\nFound all messages. Closing for now.\n\n";
            break 2;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "**Timed out\n";
            // echo "Timed out\n";
            break;
        case RD_KAFKA_RESP_ERR__FAIL:
            echo "**Failed to communicate with broker\n";
            break;
        case RD_KAFKA_RESP_ERR__BAD_MSG:
            echo "**Bad message format\n";
            break;
        case RD_KAFKA_RESP_ERR__RESOLVE:
            echo "**Host resolution failure";
            break;
        case RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC:
            echo "**unknown topic\n";
            break;
        case RD_KAFKA_RESP_ERR_INVALID_GROUP_ID:
            echo "**invalid group id\n";
            break;
        case RD_KAFKA_RESP_ERR_GROUP_AUTHORIZATION_FAILED:
            echo "**group auth failed\n";
            break;
        default:
            echo "**Unknown Error: ".$message->err."\n";
            break;

    }
}

echo count($keys)." keys that have the multple messages\n";
foreach($keys as $key => $payloads) {
    if (count($payloads) > 1) {
        echo $key." has ".count($payloads)." messages.\n";
        for ($i=0; $i < count($payloads); $i++) { 
            if ($i == 0) {
                continue;
            }
            $diff = xdiff_string_diff($payloads[($i-1)], $payloads[$i]);
            echo (($diff) ? $diff : 'NO DIFFERENCE')."\n";
        }
        echo "-------\n";
    }
}