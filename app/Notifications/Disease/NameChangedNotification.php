<?php

namespace App\Notifications\Disease;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;

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
            'template' => 'email.curations.disease_name_changed'
        ];
    }

    public static function getUnique(Collection $items): Collection
    {
        return $items->unique(function ($item) {
            return 'disease-name-changed-'.$item->data['curation']['id'].'-'.$item->data['curation']['mondo_id'];
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

}
