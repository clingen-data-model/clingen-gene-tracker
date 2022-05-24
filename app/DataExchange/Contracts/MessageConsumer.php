<?php
declare(strict_types=1);

namespace App\DataExchange\Contracts;

interface MessageConsumer
{
    /**
     * sets a topic
     *
     * @param String $topic topic name
     *
     * @return MessageConsumer
     */
    public function addTopic(String $topic): MessageConsumer;
    
    /**
     * remove a topic subscription
     */
    public function removeTopic(String $topic): MessageConsumer;
        
    /**
     * Consumes incoming messages until end-of-file exception
     *
     * @return MessageConsumer
     */
    public function consume(?callable $callback = null): MessageConsumer;

    /**
     * Consumes messages and runs callback until EOF of timeout.
     *
     * @return MessageConsumer
     */
    public function consumePresentMessages(?callable $callback = null): MessageConsumer;

    /**
     * Consumes $number of messages and runs callbacka.
     *
     * @return MessageConsumer
     */
    public function consumeSomeMessages($number, ?callable $callback = null): MessageConsumer;


    /**
     * Listen to topic until told to stop
     *
     * @return MessageConsumer
     */
    public function listen(): MessageConsumer;

    /**
     * @return Array List of topics
     */
    public function listTopics(): array;
}
