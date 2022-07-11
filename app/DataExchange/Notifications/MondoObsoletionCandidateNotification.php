<?php

namespace App\DataExchange\Notifications;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;

class MondoObsoletionCandidateNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

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

    public static function getUnique(Collection $collection):Collection
    {
        return $collection->unique(function ($item) {
            return $item->data['curation']['id'].'-'.$item->data['curation']['mondo_id'];
        });
    }
    public static function filterInvalid(Collection $collection):Collection
    {
        return $collection;
    }
    public static function getValidUnique(Collection $collection):Collection
    {
        return static::getUnique(static::filterInvalid($collection));
    }
    public static function getDigestTemplate(): string
    {
        return 'email.digest.mondo_obsoletion_candidate';
    }
}
