<?php

namespace App\DataExchange\Notifications;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;
use App\Notifications\HasValidUniqueNotifications;

class MondoObsoletionCandidateNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public Curation $curation, public $messageData)
    {
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

    public function toArray($notifiable)
    {
        return [
            'curation' => $this->curation,
            'message_data' => $this->messageData,
            'template' => 'email.digest.mondo_obsoletion_candidate',
        ];
    }

    public static function digestUniqueKey($item): string
    {
        return static::digestKeyForFields($item, ['curation.id', 'curation.mondo_id', 'message_data.content']);
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.mondo_obsoletion_candidate';
    }
}
