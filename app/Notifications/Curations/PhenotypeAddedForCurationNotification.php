<?php

namespace App\Notifications\Curations;

use App\Curation;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;

class PhenotypeAddedForCurationNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Curation $curation, private Phenotype $phenotype)
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

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage);
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
            'curation' => $this->curation->only('id', 'gene_symbol'),
            'phenotype' => $this->phenotype->only('id', 'name'),
            'template' => 'email.digest.phenotype_added'
        ];
    }

    public static function uniqueStringForItem($item): string
    {
        return $item->data['curation']['id'].'-'.$item->data['phenotype']['id'];
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
        return $collection;
    }
    public static function getDigestTemplate(): string
    {
        return 'email.digest.phenotype_added';
    }
}
