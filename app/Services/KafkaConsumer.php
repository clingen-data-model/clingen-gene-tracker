<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\MessageConsumer;
use Illuminate\Contracts\Events\Dispatcher;
use App\Services\Kafka\ErrorMessageHandler;
use App\Services\Kafka\NoActionMessageHandler;
use App\Services\Kafka\SuccessfulMessageHandler;

/**
 * @property array $topics
 */
class KafkaConsumer implements MessageConsumer
{
    protected $kafkaConsumer;
    protected $topics = [];
    protected $listening = false;
    protected $eventDispatcher;

    public function __construct(\RdKafka\KafkaConsumer $kafkaConsumer, Dispatcher $eventDispatcher)
    {
        $this->kafkaConsumer = $kafkaConsumer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __get($key)
    {
        if ($key == 'topics') {
            return $this->topics;
        }
    }

    /**
     * Add a topic to consume
     *
     * @param string $topicName Name of topic to add
     * @return MessageConsumer
     */
    public function addTopic(String $topicName):MessageConsumer
    {
        array_push($this->topics, $topicName);
        $this->cleanTopics();
        return $this;
    }

    /**
     * Remove topic from topic list
     *
     * @param string $topicName Name of topic to remove from topic list
     * @return MessageConsumer
     */
    public function removeTopic(String $topicName):MessageConsumer
    {
        if (in_array($topicName, $this->topics)) {
            unset($this->topics[array_search($topicName, $this->topics)]);
            $this->cleanTopics();
        }

        return $this;
    }

    /**
     * Get a list of topics currently being consumed
     *
     * @retrun array List of topic names
     */
    public function listTopics(): array
    {
        $availableTopics = $this->kafkaConsumer->getMetadata(true, null, 60e3)->getTopics();

        return array_map(
            function ($topic) {
                return [
                    'name' => $topic->getName(),
                    'offset' => $topic->getOffset()
                ];
            },
            $availableTopics
        );
    }
    

    /**
     * Begin listening for messages on topics in topic list
     *
     * @return MessageConsumer
     */
    public function listen(): MessageConsumer
    {
        $this->kafkaConsumer->subscribe($this->topics);

        $successHandler = new SuccessfulMessageHandler($this->eventDispatcher);
        $successHandler->setNext(app()->make(NoActionMessageHandler::class))
            ->setNext(app()->make(ErrorMessageHandler::class));

        $message = $this->kafkaConsumer->consume(10000);
        try {
            $successHandler->handle($message);
        } catch (\Throwable $th) {
            report($th);
        }

        return $this;
    }
    
    /**
     * Make sure topic list is unique.
     */
    private function cleanTopics()
    {
        $this->topics = array_unique($this->topics);
        $this->topics = array_values($this->topics);
        if ($this->listening) {
            $this->listen();
        }
    }
}
