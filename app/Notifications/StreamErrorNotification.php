<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class StreamErrorNotification extends Notification
{
    use Queueable;

    protected $streamErrors;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($streamErrors)
    {
        //
        $this->streamErrors = $streamErrors;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $uniqueErrors = $this->streamErrors
                            ->unique(function ($error) {
                                return $error->gene.'-'.$error->condition.'-'.$error->moi;
                            });

        return [
            'stream_errors' => $uniqueErrors,
            'template' => 'email.stream_error_notification'
        ];
    }
}
