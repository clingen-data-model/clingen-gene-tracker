<?php

namespace App\Notifications\Curations;

use App\Curation;
use App\Exceptions\HttpNotFoundException;
use App\Notifications\DigestibleNotificationInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MondoIdNotFound extends Notification implements DigestibleNotificationInterface
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
        $this->curation = $curation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
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
     */
    public function toArray($notifiable): array
    {
        return [
            'curation' => $this->curation,
            'template' => 'email.digest.mondoid_not_found',
        ];
    }

    public static function uniqueStringForItem($item): string
    {
        return $item->data['curation']['id'].'-'.$item->data['curation']['mondo_id'];
    }

    public static function getUnique(Collection $collection): Collection
    {
        return $collection->unique(function ($item) {
            return static::uniqueStringForItem($item);
        });
    }

    public static function filterInvalid(Collection $collection): Collection
    {
        return $collection->filter(function ($item) {
            $curation = Curation::find($item->data['curation']['id']);
            if (! $curation) {
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

    public static function getValidUnique(Collection $collection): Collection
    {
        return static::filterInvalid(static::getUnique($collection));
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.mondoid_not_found';
    }
}
