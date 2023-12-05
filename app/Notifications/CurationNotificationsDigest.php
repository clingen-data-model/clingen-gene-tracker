<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class CurationNotificationsDigest extends Notification
{
    use Queueable;

    public $groupedNotifications;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Collection $groupedNotifications)
    {
        //
        $this->groupedNotifications = $groupedNotifications;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->view('email.curation_notifications_digest', ['groups' => $this->groupedNotifications]);
    }
}
