<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Curations\MondoIdNotFound;
use App\Notifications\Curations\GeneSymbolUpdated;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Disease\NameChangedNotification;
use App\DataExchange\Notifications\StreamErrorNotification;
use App\Notifications\Curations\HgncIdNotFoundNotification;
use App\Notifications\Curations\PhenotypeNomenclatureUpdated;
use App\Notifications\Curations\PhenotypeAddedForCurationNotification;
use App\DataExchange\Notifications\MondoObsoletionCandidateNotification;

class CurationNotificationsDigest extends Notification
{
    use Queueable;

    public $groupedNotifications;
    private $map;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Collection $groupedNotifications)
    {
        //
        $this->groupedNotifications = $groupedNotifications;
        $this->map = [
            GeneSymbolUpdated::class => 'email.digest.gene_symbol_updated',
            HgncIdNotFoundNotification::class => 'email.digest.hgncid_not_found',
            PhenotypeNomenclatureUpdated::class => 'email.digest.pheno_name_updated',
            MondoIdNotFound::class => 'email.digest.mondoid_not_found',
            StreamErrorNotification::class => 'email.digest.stream_error',
            NameChangedNotification::class => 'email.digest.disease_name_changed',
            PhenotypeAddedForCurationNotification::class => 'email.digest.phenotype_added',
            MondoObsoletionCandidateNotification::class => 'email.digest.mondo_obsoletion_candidate'
        ];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
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
                    ->view('email.curation_notifications_digest', ['groups' => $this->groupedNotifications, 'templateMap' => $this->map]);
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
            //
        ];
    }
}
