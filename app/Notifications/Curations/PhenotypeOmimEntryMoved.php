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

class PhenotypeOmimEntryMoved extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    private $curation;
    private $phenotype;
    private $oldName;
    private $oldMimNumber;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, EloquentCollection $phenotypes, string $oldName, int $oldMimNumber)
    {
        //
        $this->curation = $curation;
        $this->oldName = $oldName;
        $this->phenotypes = $phenotypes;
        $this->oldMimNumber = $oldMimNumber;
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
            'template' => 'email.curations.omim_entry_moved'
        ];
    }

    public static function getUnique(Collection $collection):Collection
    {
        return $collection;
    }
    public static function filterInvalid(Collection $collection):Collection
    {
        return $collection;
    }
    public static function getValidUnique(Collection $collection):Collection
    {
        return $collection;
    }
}
