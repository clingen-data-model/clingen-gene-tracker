<?php

namespace App\Notifications\Curations;

use App\Curation;
use App\Notifications\DigestibleNotificationInterface;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PhenotypeNomenclatureUpdated extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    private $curation;
    private $oldName;
    private $phenotype;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, Phenotype $phenotype, $oldName)
    {
        $this->curation = $curation;
        $this->oldName = $oldName;
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
            'email.curations.omim_phenotype_updated',
            [
                'curation' => $this->curation,
                'oldName' => $this->oldName,
                'phenotype' => $this->phenotype
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
            'curation' => $this->curation,
            'oldName' => $this->oldName,
            'phenotype' => $this->phenotype,
            'template' => 'email.digest.pheno_name_updated'
        ];
    }
    public static function uniqueStringForItem($item): string
    {
        return $item->id;
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
    public static function getDigestTemplate(): string
    {
        return 'email.digest.pheno_name_updated';
    }
}
