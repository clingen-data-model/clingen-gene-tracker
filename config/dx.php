<?php

return [
    'broker' => env('DX_BROKER', 'pkc-4yyd6.us-east1.gcp.confluent.cloud:9092'),
    /**
     * Driver determines the message pusher used:
     *   * MessageLogger - pushes message to logs
     *   * KafkaProducer - pushes messge to configured Kafka broker & topic
     *   * DisabledPusher - does not push messages
     */
    'driver' => env('DX_ENABLE_PUSH', false)
                    ? env('DX_DRIVER', 'kafka')
                    : 'log',
    'username' => env('DX_USERNAME'),
    'password' => env('DX_PASSWORD'),
    'group' => env('DX_GROUP'),
    'push-enable' => env('DX_ENABLE_PUSH', false),
    'warn-disabled' => env('DX_WARN_DISABLED', true),
    'consume' => env('DX_CONSUME', true),
    'topics' => [
        'incoming' => [
            'gene-validity-events' => env('DX_INCOMING_GCI', 'gene_validity_events'),
            'mondo-notifications' => env('DX_INCOMING_MONDO', 'mondo-notifications')
        ],
        'outgoing' => [
            'precuration-events' => env('DX_OUTGOING_PRECURATION', 'gt-precuration-events'),
            'gt-gci-sync' => env('DX_OUTGOING_GT_GCI_SYNC', 'gt-gci'),
        ]
    ],
    // Only used for legacy kafka server. Should no longer be necessary
    'cert-location' => env('DX_CERT', '/etc/pki/tls/certs/kafka.web3demo.signed.crt'),
    'key-location' => env('DX_KEY_LOCATION', '/etc/pki/tls/private/kafka.apache.key'),
    'ca-location' => env('DX_CA_LOCATION', '/etc/pki/ca-trust/extracted/openssl/ca-kafka-cert'),
    'ssl-key-password' => env('KAFKA_KEY_PASSWORD', null),

];
