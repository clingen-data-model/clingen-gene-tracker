<?php

namespace App\DataExchange\Actions;

use App\IncomingStreamMessage;

class StoreGpmIncomingMessage
{
    public function handle(\RdKafka\Message $message, array $payload): IncomingStreamMessage 
    {
        $key = $message->key ?: implode(':', [
                $message->topic_name,
                $message->partition,
                $message->offset,
            ]);

        return IncomingStreamMessage::firstOrCreate(
            [
                'topic' => $message->topic_name,
                'partition' => $message->partition,
                'offset' => $message->offset,
            ],
            [
                'key' => $key,
                'timestamp' => $message->timestamp,
                'error_code' => $message->err ?? 0,
                'payload' => $payload,
                'gdm_uuid' => null,
            ]
        );
    }
}