<?php

namespace App\Notifications\Curations;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use App\Notifications\DigestibleNotificationInterface;

class OmimObsoletePhenotypesNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;

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

    public static function uniqueStringForItem($item): string
    {
        return $item->data['digest_key'] ?? (string) $item->id;
    }

    public static function getUnique(Collection $collection): Collection
    {
        return $collection->unique(fn ($n) => static::uniqueStringForItem($n))->values();
    }

    public static function filterInvalid(Collection $collection): Collection
    {
        return $collection->filter(function ($n) {
            return !empty($n->data['curations']) && !empty($n->data['expert_panel']['id'] ?? null);
        })->values();
    }

    public static function getValidUnique(Collection $collection): Collection
    {
        return static::getUnique(static::filterInvalid($collection));
    }

    public static function getDigestTemplate(): string
    {
        return 'email.curations.omim_obsolete_phenotypes';
    }
}