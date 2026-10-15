<?php

namespace App\Notifications\Curations;

use App\Curation;
use App\Notifications\DigestibleNotificationInterface;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use App\Notifications\HasValidUniqueNotifications;

class PhenotypeOmimEntryMoved extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private Curation $curation, 
        private EloquentCollection $phenotypes, 
        private string $oldName, 
        private int $oldMimNumber)
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
        return (new MailMessage)->view(
            'email.curations.omim_entry_moved',
            [
                'oldName' => $this->oldName,
                'oldMimNumber' => $this->oldMimNumber,
                'phenotypes' => $this->phenotypes,
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
            'oldName' => $this->oldName,
            'oldMimNumber' => $this->oldMimNumber,
            'phenotypes' => $this->phenotypes,
            'curation' => $this->curation,
            'template' => 'email.digest.omim_entry_moved'
        ];
    }

    public static function digestUniqueKey($item): string
    {
        $phenotypes = collect($item->data['phenotypes'] ?? []);
        if ($phenotypes->contains(fn ($phenotype) => !isset($phenotype['id']))) {
            return static::digestKeyForFields($item, ['curation.id', 'oldMimNumber', 'phenotypes']);
        }

        return static::digestContentKey([
            'change' => static::digestKeyForFields($item, ['curation.id', 'oldMimNumber']),
            'destinations' => $phenotypes->pluck('id')->unique()->sort()->values()->all(),
        ]);
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.omim_entry_moved';
    }
}
