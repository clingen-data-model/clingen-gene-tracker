<?php

namespace App\DataExchange\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\DataExchange\Contracts\MessageConsumer;
use App\DataExchange\Exceptions\StreamingServiceException;

class ConsumeGpmEvents
{
    use AsCommand;

    public $commandSignature = 'dx:consume-gpm {--limit= : Limit the number of messages to read from the topic at one time.}';

    private MessageConsumer $consumer;

    public function __construct(private StoreGpmIncomingMessage $storeMessage) {
    }

    public function handle($limit = null): void
    {
        $this->consumer = app()->make(MessageConsumer::class);
        $this->consumeMessages(function ($message) {
            $this->processMessage($message);
        }, $limit);
    }

    public function asCommand(Command $command): void
    {
        $this->handle($command->option('limit'));
    }

    private function processMessage(\RdKafka\Message $message): void
    {
        if (!$message->payload) {
            if ($message->err) {
                throw new StreamingServiceException($message->errstr());
            }
            return;
        }
        $payload = json_decode($message->payload, true);
        $groupType = data_get($payload, 'data.group.type') ?? data_get($payload, 'data.group.expert_panel.type');
        // GT only processes GCEP events from this topic.
        if ($groupType !== 'gcep') { return; }
        if (!is_array($payload)) {
            Log::warning('Invalid JSON received from GPM.', ['partition' => $message->partition, 'offset' => $message->offset]);
            return;
        }
        $eventType = $payload['event_type'] ?? null;
        $handlerClass = config('dx.gpm_event_handlers.'.$eventType);

        // Ignore unsupported GPM events before storing them.
        if (!$handlerClass) { return; }
        try {
            $incomingMessage = $this->storeMessage->handle($message, $payload);
            app($handlerClass)->handle($incomingMessage, $payload);
        } catch (\Throwable $exception) {
            Log::error('Failed to process a GPM DX event.', [
                'event_type' => $eventType,
                'topic' => $message->topic_name,
                'partition' => $message->partition,
                'offset' => $message->offset,
                'error' => $exception->getMessage(),
            ]);
            report($exception);
        }
    }

    private function consumeMessages(callable $callback, $limit = null): void {
        $this->consumer->addTopic(config('dx.topics.incoming.gpm-general-events'));
        if ($limit) {
            $this->consumer->consumeSomeMessages($limit, $callback);
            return;
        }
        $this->consumer->consumePresentMessages($callback);
        Log::debug('Consumed supported gpm-general-events messages.');
    }
}