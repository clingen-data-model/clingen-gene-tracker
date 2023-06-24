<?php

namespace App\DataExchange\Testing;

use App\DataExchange\Contracts\MessageConsumer;

class TestConsumer implements MessageConsumer
{
    public $messages = [];

    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    public function addTopic(string $topic): MessageConsumer
    {
        return $this;
    }

    public function consume(?callable $callable = null): MessageConsumer
    {
        while (count($this->messages) > 0) {
            $message = array_shift($this->messages);
            $callable($message);
        }

        return $this;
    }

    public function consumeSomeMessages($number, ?callable $callable = null): MessageConsumer
    {
        $count = 0;
        while (count($this->messages) > 0) {
            if ($count >= $number) {
                break;
            }
            $message = array_shift($this->messages);
            $callable($message);
            $count++;
        }

        return $this;
    }

    public function consumePresentMessages(?callable $callable = null): MessageConsumer
    {
        while (count($this->messages) > 0) {
            $message = array_shift($this->messages);
            $callable($message);
        }

        return $this;
    }

    public function listTopics(): array
    {
        return [];
    }

    public function removeTopic(string $topic): MessageConsumer
    {
        return $this;
    }

    public function listen(): MessageConsumer
    {
        return $this;
    }
}
