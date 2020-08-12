<?php

namespace App\Services\Kafka;

use App\Exceptions\KafkaEnvironmentException;

class KafkaEnvValidator
{
    public function __invoke()
    {
        $ssConfig = config('streaming-service');
        foreach (['kafka_username', 'kafka_password', 'kafka_group'] as $config) {
            if (!isset($ssConfig[$config]) || !$ssConfig[$config]) {
                Throw new KafkaEnvironmentException('Missing kafka environment variable '.strtoupper($config));
            }
        }
    }
    
}
