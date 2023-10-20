<?php

namespace App\DataExchange\MessagePushers;

use App\DataExchange\Contracts\MessagePusher;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class MessageLogger implements MessagePusher
{
    public function topic(string $topic)
    {
        $this->topic = $topic;

        return $this;
    }

    public function push(string $message, $uuid = null)
    {
        $uuid = $uuid ?? Uuid::uuid4()->toString();
        Log::info('Message Pushed', ['topic' => $this->topic, 'message' => $message, 'key' => $uuid]);
    }
}
