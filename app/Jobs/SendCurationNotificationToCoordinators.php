<?php

namespace App\Jobs;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;

class SendCurationNotificationToCoordinators implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;
    protected $notificationClass;

    protected $additional;

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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $notifiables = $this->curation->expertPanel->coordinators;
        $notification = new $this->notificationClass($this->curation, ...$this->additional);
        
        \NotificationClass::send($notifiables, $notification);
    }
}
