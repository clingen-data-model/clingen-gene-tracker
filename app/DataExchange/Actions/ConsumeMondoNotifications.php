<?php

namespace App\DataExchange\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\DataExchange\Contracts\MessageConsumer;
use App\DataExchange\Exceptions\StreamingServiceException;
use App\DataExchange\Exceptions\StreamingServiceEndOfFileException;

class ConsumeMondoNotifications
{
    use AsCommand;

    public $commandSignature = 'dx:consume-mondo {--reset-offset : Set topic offset to 0 } {--limit= : Limit the number of messages to read from the topic at one time.} {--dry-run : Consume the messages but do not send notifications; reset to 0 when finished.}';

    private MessageConsumer $consumer;


    public function __construct(
        private NotifyMondoObsoletionCandidate $notifyCandidateAction,
    )
    {
        
    }
    

    public function handle($limit = null): void
    {
        $this->consumer = app()->make(MessageConsumer::class);
        $this->consumeMessages(function ($message) {
            $payload = json_decode($message->payload);
            
            if (!$message->payload) {
                if ($message->err) {
                    throw new StreamingServiceException($message->errstr());
                }
                return;
            }
            
            if ($payload->event_type == 'obsoletion_candidate') {
                $this->notifyCandidateAction->handle($payload);
            }
        }, $limit);
    }

    public function asCommand(Command $command): void
    {
        $limit = $command->option('limit') ?? null;
        $this->handle($limit);
    }

    private function consumeMessages(callable $callback, $limit = null): void
    {
        $this->consumer->addTopic(config('dx.topics.incoming.mondo-notifications'));

        if ($limit) {
            $this->consumer->consumeSomeMessages($limit, $callback);
            return;
        }

        $this->consumer->consumePresentMessages($callback);
        \Log::debug('consumed mondo_notifications topic');
    }
}