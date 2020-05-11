<?php

namespace App\Notifications\Curations;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class GeneSymbolUpdated extends Notification
{
    use Queueable;

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
            'oldGeneSymbol' => $this->oldGeneSymbol
        ];
    }
}
