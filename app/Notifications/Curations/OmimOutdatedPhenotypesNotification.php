<?php

namespace App\Notifications\Curations;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use App\Notifications\DigestibleNotificationInterface;
use App\Notifications\HasValidUniqueNotifications;

class OmimOutdatedPhenotypesNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    public function __construct(
        public array $payload // store only arrays/scalars here
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return $this->payload;
    }

    // DigestibleNotificationInterface

    public static function digestUniqueKey($item): string
    {
        if (empty($item->data['digest_key'])) {
            return static::digestContentKey(\Illuminate\Support\Arr::except($item->data, ['template', 'digest_event_id']));
        }

        return static::digestKeyForFields($item, ['digest_key']);
    }

    public static function filterInvalid(Collection $collection): Collection
    {
        return $collection->filter(function ($n) {
            return !empty($n->data['curations']) && !empty($n->data['expert_panel']['id'] ?? null);
        })->values();
    }

    public static function getDigestTemplate(): string
    {
        return 'email.curations.omim_obsolete_phenotypes';
    }
}
