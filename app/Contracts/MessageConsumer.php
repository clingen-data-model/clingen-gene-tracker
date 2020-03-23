<?php
declare(strict_types=1);

namespace App\Contracts;

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
     * Starts listening for incoming messages
     * 
     * @return void
     */
    public function listen(): messageConsumer;

    /**
     * @return Array List of topics
     */
    public function listTopics(): Array;
    
}