<?php

return [
    /**
     * The uri of the clingen DX message broker.
     */
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

    /**
     * Cedentials used to authenticate with the broker
     */
    'username' => env('DX_USERNAME'),
    'password' => env('DX_PASSWORD'),
    'group' => env('DX_GROUP'),

    /**
     * Whether pushing messages to the broker is enabled.
     */
    'push-enable' => env('DX_ENABLE_PUSH', false),

    /**
     * Whether to write warning to the logs when pushing is disabled.
     */
    'warn-disabled' => env('DX_WARN_DISABLED', true),

    /**
     * Whether to consume incoming topics.
     */
    'consume' => env('DX_CONSUME', true),

    /**
     * Topics that this application consumes (incoming) or to which it produces (outgoing)
     */
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

];
