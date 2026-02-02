<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OmimObsoletePhenotypesDigest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $expertPanel, public $curations, public $since)
    { }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('OMIM phenotype name changes (weekly digest)')
                                ->view('email.omim_obsolete_notification', [
                                    'expertPanel' => $this->expertPanel,
                                    'curations' => $this->curations,
                                    'since' => $this->since,
                                    'user' => $notifiable,
                                ]);
        /*
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!'); */
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
