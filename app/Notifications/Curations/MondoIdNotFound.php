<?php

namespace App\Notifications\Curations;

use App\Contracts\MondoClient;
use App\Curation;
use App\Exceptions\HttpNotFoundException;
use App\Notifications\DigestibleNotificationInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\HasValidUniqueNotifications;

class MondoIdNotFound extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    protected $curation;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
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
        return (new MailMessage)->subject('Mondo id not found')
            ->view(
                'email.curations.mondo_id_not_found',
                ['curation' => $this->curation]
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
            'template' => 'email.digest.mondoid_not_found'
        ];
    }

    public static function digestUniqueKey($item): string
    {
        return static::digestKeyForFields($item, ['curation.id', 'curation.mondo_id']);
    }

    public static function uniqueStringForItem($item): string
    {
        return $item->data['curation']['id'].'-'.$item->data['curation']['mondo_id'];
    }

    public static function filterInvalid(Collection $collection):Collection
    {
        return $collection->filter(function ($item) {
            $curation = Curation::find($item->data['curation']['id']);
            if (!$curation) {
                return false;
            }
            if ($curation->mondo_id == $item->data['curation']['mondo_id']) {
                return true;
            }

            try {
                return false;
            } catch (HttpNotFoundException $e) {
                return true;
            } catch (ClientException $e) {
                return true;
            }
        });
    }
    public static function getDigestTemplate(): string
    {
        return 'email.digest.mondoid_not_found';
    }
}
