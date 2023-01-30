<?php

namespace App\Notifications\Curations;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\DigestibleNotificationInterface;

class GeneSymbolUpdated extends Notification implements DigestibleNotificationInterface
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
            'oldGeneSymbol' => $this->oldGeneSymbol,
            'template' => 'email.digest.gene_symbol_updated'
        ];
    }

    public static function uniqueStringForItem($item): string
    {
        return $item->data['curation']['id'].'-'.$item->data['curation']['gene_symbol'];
    }

    public static function getUnique(Collection $items): Collection
    {
        return $items->unique(function ($item) {
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
        return 'email.digest.gene_symbol_updated';
    }
}
