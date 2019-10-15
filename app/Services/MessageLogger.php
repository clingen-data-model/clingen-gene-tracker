<?php

namespace App\Services;

use App\Contracts\MessagePusher;

class MessageLogger implements MessagePusher
{
    public function topic(string $topic)
    {
        $this->topic = $topic;
        return $this;
    }

    public function push(string $message)
    {
        \Log::info('Message Pushed', ['topic' => $this->topic, 'message' => $message]);
    }
}
