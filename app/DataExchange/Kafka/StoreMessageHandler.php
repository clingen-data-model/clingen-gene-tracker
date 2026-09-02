<?php

namespace App\DataExchange\Kafka;

use App\IncomingStreamMessage;
use Illuminate\Support\Facades\Log;

class StoreMessageHandler extends AbstractMessageHandler
{
    public function handle(\RdKafka\Message $message)
    {
        $payload = json_decode($message->payload);
        $key = $this->hasUuid($message->payload) ? $payload->report_id.'-'.$payload->date : null;

        $storedMessage = IncomingStreamMessage::firstOrCreate([
            'key' => $key,
        ], [
            'timestamp' => $message->timestamp,
            'topic' => $message->topic_name,
            'partition' => $message->partition,
            'offset' => $message->offset,
            'error_code' => $message->err,
            'payload' => $payload,
            'gdm_uuid' => $this->hasUuid($message->payload) ? $payload->report_id : null
        ]);

        if ($storedMessage->payload != $payload) {
            // Same business key, different content: we cannot tell which is authoritative,
            // so record nothing and let a human decide. Do not dispatch downstream.
            Log::error('gene_validity_events message reuses an existing key with a different payload', [
                'key' => $key,
                'stored_payload' => $storedMessage->payload,
                'incoming_payload' => $payload,
            ]);

            return;
        }

        // Kafka delivery is at-least-once. A message we have already stored has already
        // been dispatched, so re-dispatching it would re-run every downstream listener.
        // Intentional re-processing goes through gci:replay, which reads
        // incoming_stream_messages directly and bypasses this chain.
        if (!$storedMessage->wasRecentlyCreated) {
            Log::info('Skipping already-consumed gene_validity_events message', ['key' => $key]);

            return;
        }

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
