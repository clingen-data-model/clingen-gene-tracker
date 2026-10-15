<?php

namespace App\Notifications\Curations;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;
use App\Notifications\HasValidUniqueNotifications;

class GeneSymbolUpdated extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    private $curation;
    private $oldGeneSymbol;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, string $oldGeneSymbol)
    {
        $this->curation = $curation;
        $this->oldGeneSymbol = $oldGeneSymbol;
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
                ->view('email.curations.gene_symbol_updated', ['oldGeneSymbol' => $this->oldGeneSymbol, 'curation' => $this->curation]);
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
            'oldGeneSymbol' => $this->oldGeneSymbol,
            'template' => 'email.digest.gene_symbol_updated'
        ];
    }

    public static function digestUniqueKey($item): string
    {
        return static::digestKeyForFields($item, ['curation.id', 'oldGeneSymbol', 'curation.gene_symbol']);
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.gene_symbol_updated';
    }
}
