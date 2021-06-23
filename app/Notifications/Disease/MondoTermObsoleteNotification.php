<?php

namespace App\Notifications\Disease;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MondoTermObsoleteNotification extends Notification
{
    use Queueable;

    public $curation;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        \Log::debug(__METHOD__);
        $this->curation = $curation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        \Log::debug('via [mail]');
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $view = 'email.curations.mondo_term_obsoleted';
        \Log::debug($view);
        return (new MailMessage)->view($view, ['curation' => $this->curation, 'notifiable' => $notifiable]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
