<?php

namespace App\Notifications\Curations;

use App\Curation;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PhenotypeNomenclatureUpdated extends Notification
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
            'template' => 'email.curations.omim_phenotype_updated'
        ];
    }
}
