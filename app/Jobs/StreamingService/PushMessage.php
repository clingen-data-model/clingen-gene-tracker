<?php

namespace App\Jobs\StreamingService;

use Carbon\Carbon;
use App\StreamMessage;
use Illuminate\Bus\Queueable;
use App\Contracts\MessagePusher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Exceptions\StreamingServiceException;
use App\Exceptions\StreamingServiceDisabledException;

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
        //
        $this->message = $message;
        // dd($this->message);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MessagePusher $pusher)
    {
        try {
            $pusher->topic($this->message->topic);
            $pusher->push($this->message->message);
            $this->message->update([
                'sent_at' => Carbon::now()
            ]);
            // dump('updated message setn_at');
        } catch (StreamingServiceDisabledException $e) {
            if (config('streaming-service.warn-disabled', true)) {
                \Log::warning($e->getMessage());
            }
        } catch (StreamingServiceException $e) {
            report($e);
            return;
        }
    }
}
