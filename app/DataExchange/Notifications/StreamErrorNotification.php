<?php

namespace App\DataExchange\Notifications;

use App\Notifications\DigestibleNotificationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class StreamErrorNotification extends Notification implements DigestibleNotificationInterface
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
                                return $error->gene->gene.'-'.$error->condition.'-'.$error->moi;
                            });

        return [
            'stream_errors' => $uniqueErrors,
            'template' => 'email.digest.stream_error',
        ];
    }

    public static function uniqueStringForItem($item): string
    {
        return $item->id;
    }

    public static function getUnique(Collection $collection): Collection
    {
        return $collection;
    }

    public static function filterInvalid(Collection $collection): Collection
    {
        return $collection;
    }

    public static function getValidUnique(Collection $collection): Collection
    {
        return $collection;
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.stream_error';
    }
}
