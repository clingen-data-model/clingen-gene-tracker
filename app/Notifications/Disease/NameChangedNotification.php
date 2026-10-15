<?php

namespace App\Notifications\Disease;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;
use App\Notifications\HasValidUniqueNotifications;

class NameChangedNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    public $curation;
    public $oldName;
    
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, Array $additional)
    {
        $this->curation = $curation;
        $this->oldName = $additional['oldName'];
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
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->view('emails.curations.disease_name_changed', ['curation' => $this->curation, 'oldName' => $this->oldName]);
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
            'curation' => $this->curation,
            'oldName' => $this->oldName,
            'template' => 'email.digest.disease_name_changed'
        ];
    }

    public static function digestUniqueKey($item): string
    {
        return static::digestKeyForFields($item, ['curation.id', 'curation.mondo_id', 'oldName', 'curation.disease.name']);
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.disease_name_changed';
    }

}
