<?php

namespace App\Services\Kafka;

use App\IncomingStreamMessage;

class StoreMessageHandler extends AbstractMessageHandler
{
    public function handle(\RdKafka\Message $message)
    {
        $payload = json_decode($message->payload);
        IncomingStreamMessage::firstOrCreate([
            'key' => $this->hasUuid($message->payload) ? $payload->report_id.'-'.$payload->date : null,
            'payload' => $message->payload,
        ], [
            'timestamp' => $message->timestamp,
            'topic' => $message->topic_name,
            'partition' => $message->partition,
            'offset' => $message->offset,
            'error_code' => $message->err,
            'payload' => $payload,
            'gdm_uuid' => $this->hasUuid($message->payload) ? $payload->report_id : null
        ]);
        // IncomingStreamMessage::create([
        //     'key' => $this->hasUuid($message->payload) ? $payload->report_id.'-'.$payload->date : null,
        //     'timestamp' => $message->timestamp,
        //     'topic' => $message->topic_name,
        //     'partition' => $message->partition,
        //     'offset' => $message->offset,
        //     'error_code' => $message->err,
        //     'payload' => $payload,
        //     'gdm_uuid' => $this->hasUuid($message->payload) ? $payload->report_id : null
        // ]);
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
