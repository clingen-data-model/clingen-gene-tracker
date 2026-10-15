<?php

namespace App\Notifications\Curations;

use App\Curation;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;
use Illuminate\Support\Collection;
use App\Notifications\HasValidUniqueNotifications;

class PhenotypeOmimEntryRemoved extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    private $curation;
    private $phenotype;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, Phenotype $phenotype)
    {
        //
        $this->curation = $curation;
        $this->phenotype = $phenotype;
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
        return (new MailMessage)->view(
            'email.curations.omim_entry_removed',
            [
                'phenotype' => $this->phenotype,
                'curation' => $this->curation,
            ]
        );
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
            'phenotype' => $this->phenotype,
            'curation' => $this->curation,
            'template' => 'email.curations.omim_entry_moved'
        ];
    }

    public static function digestUniqueKey($item): string
    {
        return static::digestKeyForFields($item, ['curation.id', 'phenotype.id']);
    }

    public static function getDigestTemplate(): string
    {
        return 'email.curations.omim_entry_moved';
    }
}
