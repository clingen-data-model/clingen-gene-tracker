<?php

namespace App\DataExchange\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use App\Notifications\DigestibleNotificationInterface;
use App\Notifications\HasValidUniqueNotifications;

class StreamErrorNotification extends Notification implements DigestibleNotificationInterface
{
    use Queueable;
    use HasValidUniqueNotifications;

    protected $streamErrors;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($streamErrors)
    {
        //
        $this->streamErrors = $streamErrors;
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
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $uniqueErrors = $this->streamErrors
                            ->unique(function ($error) {
                                return $error->gene->gene.'-'.$error->condition.'-'.$error->moi;
                            });
        return [
            'stream_errors' => $uniqueErrors,
            'template' => 'email.digest.stream_error'
        ];
    }

    public static function digestUniqueKey($item): string
    {
        return static::digestContentKey(collect($item->data['stream_errors'] ?? [])
            ->map(fn ($error) => static::errorDigestKey($error))->unique()->sort()->values()->all());
    }

    public static function getUnique(Collection $collection): Collection
    {
        $seen = [];

        return $collection->map(function ($item) use (&$seen) {
            $errors = collect($item->data['stream_errors'] ?? [])->filter(function ($error) use (&$seen) {
                $key = static::errorDigestKey($error);
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;
                return true;
            })->values()->all();

            // Only the email snapshot changes; never mutate the stored batch.
            $snapshot = clone $item;
            $snapshot->data = array_replace($item->data, ['stream_errors' => $errors]);
            return $snapshot;
        })->filter(fn ($item) => !empty($item->data['stream_errors']))->values();
    }

    private static function errorDigestKey(array $error): string
    {
        $condition = data_get($error, 'message_payload.gene_validity_evidence_level.genetic_condition');
        $report = data_get($error, 'message_payload.report_id');
        $affiliation = data_get($error, 'affiliation.id')
            ?? data_get($error, 'message_payload.performed_by.on_behalf_of.id');
        if (!$condition || !$report || !$affiliation) {
            return static::digestContentKey($error);
        }

        return static::digestContentKey([
            'report' => $report,
            'affiliation' => $affiliation,
            'condition' => $condition,
            'gene' => data_get($error, 'gene_model.gene_symbol', $error['gene'] ?? null),
            'disease' => data_get($error, 'disease_model.name', $error['condition'] ?? null),
            'moi' => data_get($error, 'moi_model.name', $error['moi'] ?? null),
        ]);
    }

    public static function uniqueStringForItem($item): string
    {
        return $item->id;
    }

    public static function getDigestTemplate(): string
    {
        return 'email.digest.stream_error';
    }

}
