<?php
declare(strict_types=1);

namespace App\Services\Kafka;

use App\Contracts\Kafka\MessageHandler;
use App\Exceptions\StreamingServiceException;

class ErrorMessageHandler extends AbstractMessageHandler
{
    public function handle(\RdKafka\Message $message)
    {
        if (in_array($message->err, $this->knownErrors)) {
            $errMsg = ($message->payload) ? $message->payload : 'An unknown error occurred while consuming Kafka messages';
            throw new StreamingServiceException($errMsg, $message->err);
        }

        return parent::handle($message);
    }
}
