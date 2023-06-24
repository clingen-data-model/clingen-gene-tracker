<?php

declare(strict_types=1);

namespace App\DataExchange\Contracts;

interface MessageConsumer
{
    /**
     * sets a topic
     *
     * @param  string  $topic topic name
     */
    public function addTopic(string $topic): MessageConsumer;

    /**
     * remove a topic subscription
     */
    public function removeTopic(string $topic): MessageConsumer;

    /**
     * Consumes incoming messages until end-of-file exception
     */
    public function consume(?callable $callback = null): MessageConsumer;

    /**
     * Consumes messages and runs callback until EOF of timeout.
     */
    public function consumePresentMessages(?callable $callback = null): MessageConsumer;

    /**
     * Consumes $number of messages and runs callbacka.
     */
    public function consumeSomeMessages($number, ?callable $callback = null): MessageConsumer;

    /**
     * Listen to topic until told to stop
     */
    public function listen(): MessageConsumer;

    /**
     * @return array List of topics
     */
    public function listTopics(): array;
}
