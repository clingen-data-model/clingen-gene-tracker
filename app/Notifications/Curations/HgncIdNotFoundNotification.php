<?php

namespace App\Notifications\Curations;

use App\Curation;
use App\Hgnc\HgncClientContract;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use App\Exceptions\HttpNotFoundException;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Exceptions\HttpUnexpectedResponseException;
use App\Notifications\DigestibleNotificationInterface;

class HgncIdNotFoundNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

    protected $curation;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        //
        $this->curation = $curation;
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
            ->subject('There\'s an issue with one of your curations')
            ->view('email.digest.hgncid_not_found', ['curation' => $this->curation]);
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
            'template' => 'email.curations.hgnc_id_not_found'
        ];
    }

    public static function getUnique(Collection $collection):Collection
    {
        return $collection->unique(function ($item) {
            return $item->data['curation']['id'].'-'.$item->data['curation']['gene_symbol'];
        });
    }

    public static function filterInvalid(Collection $collection):Collection
    {
        $hgncClient = app()->make(HgncClientContract::class);
        return $collection->filter(function ($item) use ($hgncClient) {
            $curation = Curation::find($item->data['curation']['id']);
            if (!is_null($curation->hgnc_id)) {
                return false;
            }
            if ($curation->gene_symbol == $item->data['curation']['gene_symbol']) {
                return true;
            }
    
            try {
                $hgncClient->fetchGeneSymbol($curation->gene_symbol);
                return false;
            } catch (HttpNotFoundException $e) {
                return true;
            } catch (HttpUnexpectedResponseException $e) {
                return true;
            }
        });
    }

    public static function getValidUnique($collection):Collection
    {
        return static::filterInvalid(static::getUnique($collection));
    }
}
