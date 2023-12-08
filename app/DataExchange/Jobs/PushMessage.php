<?php

namespace App\DataExchange\Jobs;

use Illuminate\Support\Facades\Log;
use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\Exceptions\StreamingServiceDisabledException;
use App\DataExchange\Exceptions\StreamingServiceException;
use App\StreamMessage;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(StreamMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(MessagePusher $pusher): void
    {
        try {
            $pusher->topic($this->message->topic);
            $message = $this->getMessageString($this->message->message);
            $pusher->push($message);
            $this->message->update([
                'sent_at' => Carbon::now(),
            ]);
        } catch (StreamingServiceDisabledException $e) {
            if (config('dx.warn-disabled', true)) {
                Log::warning($e->getMessage());
            }
        } catch (StreamingServiceException $e) {
            report($e);

            return;
        }
    }

    private function getMessageString($message)
    {
        if (is_string($message)) {
            return $message;
        }

        if (is_array($message) || is_object($message)) {
            return json_encode($message);
        }

        throw new Exception('Expected message to be string, object, or array.  Got '.gettype($message));
    }
}
