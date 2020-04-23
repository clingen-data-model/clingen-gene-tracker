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
            // dump('RD_KAFKA_RESP_ERR__TIMED_OUT');
            return;
        }

        return parent::handle($message);
    }
}
