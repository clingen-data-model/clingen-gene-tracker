<?php
declare(strict_types=1);

namespace App\Services\Kafka;

class NoActionMessageHandler extends AbstractMessageHandler
{
    private $noActionErrors = [
        RD_KAFKA_RESP_ERR__TIMED_OUT
    ];

    public function handle(\RdKafka\Message $message)
    {
        if (in_array($message->err, $this->noActionErrors)) {
            return;
        }

        if ($this->isUnpublishedMessage($message)) {
            return;
        }

        return parent::handle($message);
    }

    private function isUnpublishedMessage(\RdKafka\Message $message)
    {
        $payload = json_decode($message->payload);
        return !is_null($payload) && (is_object($payload->status) && $payload->status->name == 'unpublished' || $payload->status == 'unpublished');
    }
    
}
