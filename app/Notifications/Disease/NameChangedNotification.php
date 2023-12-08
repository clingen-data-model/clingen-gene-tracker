<?php

namespace App\Notifications\Disease;

use App\Curation;
use App\Notifications\DigestibleNotificationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NameChangedNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    public $curation;

    public $oldName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, array $additional)
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
            'template' => 'email.digest.disease_name_changed',
        ];
    }

    public static function uniqueStringForItem($item): string
    {
        return 'disease-name-changed-'.$item->data['curation']['id'].'-'.$item->data['curation']['mondo_id'];
    }

    public static function getUnique(Collection $items): Collection
    {
        return $items->unique(function ($item) {
            return static::uniqueStringForItem($item);
        });
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
        return 'email.digest.disease_name_changed';
    }
}
