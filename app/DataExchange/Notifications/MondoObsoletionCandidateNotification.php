<?php

namespace App\DataExchange\Notifications;

use App\Curation;
use App\Notifications\DigestibleNotificationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

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
    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'curation' => $this->curation,
            'message_data' => $this->messageData,
            'template' => 'email.digest.mondo_obsoletion_candidate',
        ];
    }

    public static function uniqueStringForItem($item): string
    {
        return $item->data['curation']['id'].'-'.$item->data['curation']['mondo_id'];
    }

    public static function getUnique(Collection $collection): Collection
    {
        return $collection->unique(function ($item) {
            return static::uniqueStringForItem($item);
        });
    }

    public static function filterInvalid(Collection $collection): Collection
    {
        return $collection;
    }

    public static function getValidUnique(Collection $collection): Collection
    {
        return static::getUnique(static::filterInvalid($collection));
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.mondo_obsoletion_candidate';
    }
}
