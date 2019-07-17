<?php

return [
    'cert-location' => env('KAFKA_CERT', '/etc/pki/tls/certs/kafka.web3demo.signed.crt'),
    'key-location' => env('KAFKA_KEY_LOCATION', '/etc/pki/tls/private/kafka.apache.key'),
    'ca-location' =>  env('KAFKA_CA_LOCATION', '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert'),
    'ssl-key-password' => env('KAFKA_KEY_PASSWORD', null),
    'group' => env('KAFKA_GROUP', 'unc_demo')
];