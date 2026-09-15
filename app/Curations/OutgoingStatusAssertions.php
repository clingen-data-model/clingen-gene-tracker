<?php

namespace App\Curations;

use App\CurationStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What this app's own outgoing "precuration-events" messages say a curation's
 * status was, and when the system said so.
 *
 * A message is produced whenever a save changed the curation at all -- not only
 * a status change, since it hangs off Eloquent's native `updated`/`created`
 * events -- and its payload echoes whatever was the current status at that
 * moment: `status.name` and `status.effective_date`, read straight off the row
 * a backfill is trying to recover. The message's own `created_at` is a
 * timestamp nothing else can dispute: the listener that wrote it ran within
 * moments of the save that changed the projection.
 *
 * Deliberately excluded: the one-time baseline dump from `gci:produce-baseline`
 * (topic `gt-gci-sync`, event `precuration_completed`). Its created_at/sent_at
 * are when the dump ran, not when any status changed -- using them would
 * misdate every curation it touched to the same handful of instants. Filtering
 * to the `precuration-events` topic and `created`/`updated` events excludes it,
 * along with the unrelated `gdm_updated`/`curation_archived` events on
 * `gt-gci-sync`.
 *
 * Also excluded: a message whose created_at falls on a different day than the
 * status it echoes. The listener is queued, and a queue that stalls and later
 * catches up on a backlog runs every job at once -- observed directly in dev,
 * where a stuck worker resuming stamped thousands of messages with today's
 * date while echoing statuses set years earlier. Nothing distinguishes that
 * message from an organic one by content; the same-day check is what does.
 */
class OutgoingStatusAssertions
{
    /** @var array<int, array{status_id: int, instant: string, observed_at: string}[]> */
    private array $byCuration;

    public function __construct()
    {
        $this->byCuration = $this->load();
    }

    /**
     * Assertions for one curation, in the order the messages were created --
     * earliest first, so a consumer taking "the first match" gets the instant
     * closest to when the status actually became current.
     *
     * @return array{status_id: int, instant: string, observed_at: string}[]
     */
    public function forCuration(int $curationId): array
    {
        return $this->byCuration[$curationId] ?? [];
    }

    /**
     * @return array<int, array{status_id: int, instant: string, observed_at: string}[]>
     */
    private function load(): array
    {
        $statusIds = CurationStatus::pluck('id', 'name');

        $rows = DB::table('stream_messages')
            ->where('topic', config('dx.topics.outgoing.precuration-events'))
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(message, '$.event_type')) IN ('created', 'updated')")
            ->orderBy('id')
            ->get(['message', 'created_at']);

        $byCuration = [];

        foreach ($rows as $row) {
            $payload = json_decode($row->message);
            $data = $payload->data ?? null;
            $status = $data->status ?? null;

            if (!$data || !isset($data->id) || !$status || !isset($status->name, $status->effective_date)) {
                continue;
            }

            $statusId = $statusIds->get($status->name);

            if (!$statusId) {
                continue;
            }

            $instant = Carbon::parse($status->effective_date);
            $observedAt = Carbon::parse($row->created_at);

            if (!$observedAt->isSameDay($instant)) {
                continue;
            }

            $byCuration[(int) $data->id][] = [
                'status_id' => (int) $statusId,
                'instant' => $instant->format('Y-m-d H:i:s'),
                'observed_at' => (string) $row->created_at,
            ];
        }

        return $byCuration;
    }
}
