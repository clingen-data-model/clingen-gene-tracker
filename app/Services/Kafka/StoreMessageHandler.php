<?php

namespace App\Services\Kafka;

use App\IncomingStreamMessage;

class StoreMessageHandler extends AbstractMessageHandler
{
    public function handle(\RdKafka\Message $message)
    {
        IncomingStreamMessage::create([
            'topic' => $message->topic_name,
            'partition' => $message->partition,
            'offset' => $message->offset,
            'error_code' => $message->err,
            'payload' => json_decode($message->payload),
            'gdm_uuid' => $this->hasUuid($message->payload) ? json_decode($message->payload)->report_id : null
        ]);
        return parent::handle($message);
    }

    private function hasUuid($payload)
    {
        $data = json_decode($payload);
        if ($data && is_object($data) && isset($data->report_id)) {
            return true;
        }
        return false;
    }
}
