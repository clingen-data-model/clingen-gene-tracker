<?php

namespace App\DataExchange\Kafka;

use App\DataExchange\Exceptions\KafkaEnvironmentException;

class KafkaEnvValidator
{
    public function __invoke()
    {
        $ssConfig = config('dx');
        foreach (['dx_username', 'dx_password', 'dx_group'] as $config) {
            if (!isset($ssConfig[$config]) || !$ssConfig[$config]) {
                throw new KafkaEnvironmentException('Missing kafka environment variable '.strtoupper($config));
            }
        }
    }
}
