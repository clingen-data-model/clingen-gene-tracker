<?php

namespace App\Jobs;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;

class NotifyCoordinatorsAboutCuration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;
    protected $notificationClass;

    protected $additional;
    protected ?string $digestEventId = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, string $notificationClass, ...$additional)
    {
        //
        $this->curation = $curation;
        $this->notificationClass = $notificationClass;
        $this->additional = $additional;
        $this->digestEventId = (string) \Illuminate\Support\Str::uuid();
    }

    public function withDigestEventId(string $id): static
    {
        $this->digestEventId = $id;
        return $this;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Older queued jobs have no stored ID. Queue UUID survives retries.
        $this->digestEventId ??= $this->job?->uuid() ?? (string) \Illuminate\Support\Str::uuid();
        $this->curation
            ->expertPanel
            ->coordinators()
            ->active()
            ->get()
            ->each(function ($user) {
                $notification = new $this->notificationClass($this->curation, ...$this->additional);
                if (method_exists($notification, 'withDigestEventId')) {
                    $notification->withDigestEventId($this->digestEventId);
                }
                $user->notify($notification);
            });
    }
}
