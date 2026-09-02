<?php

namespace App\Console\Commands\Curations;

use App\Actions\Curations\ProjectCurationField;
use App\Curation;
use App\Curations\CurationField;
use App\Curations\DuplicateKey;
use App\DataExchange\Maps\GciStatusMap;
use App\Exceptions\GciSyncException;
use App\Gci\GciMessage;
use App\IncomingStreamMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Recovers the time of day for status history rows that were truncated to midnight.
 *
 * Status dates used to be stored as dates, so a curation that moved twice in one
 * day left rows nothing could order. Two sources can put the time back:
 *
 *  - the GCI messages, whose emission times are still stored verbatim in
 *    incoming_stream_messages. Their emission times are used rather than their
 *    status.date, which GCI fills with a synthetic fixed time for approved
 *    messages and which therefore misorders a same-day approve/publish pair;
 *  - the row's own created_at, where the row was written the same day it is dated,
 *    which makes the write time the moment the status was recorded.
 *
 * A manually entered date backdated by a curator has no time anywhere, and those
 * rows are left at midnight. Midnight now means "time unknown".
 */
class RestoreStatusTimestamps extends Command
{
    protected $signature = 'curations:restore-status-timestamps
                            {curation? : Restrict to one curation, by any of its ids}
                            {--dry-run : Report what would change without writing}
                            {--chunk=200}';

    protected $description = 'Recover the time of day for status history rows truncated to midnight';

    private GciStatusMap $statusMap;

    private array $counts = [
        'gci message' => 0,
        'row write time' => 0,
        'time unknown' => 0,
        'day left whole' => 0,
        'collision' => 0,
    ];

    private array $samples = [];

    private array $moved = [];

    public function handle(GciStatusMap $statusMap): int
    {
        $this->statusMap = $statusMap;

        $dryRun = (bool) $this->option('dry-run');
        $query = $this->query();

        if ($query === null) {
            return self::FAILURE;
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No status history rows are missing a time.');

            return self::SUCCESS;
        }

        $this->info($total.' curation(s) have status rows recorded at midnight.');

        if ($dryRun) {
            DB::beginTransaction();
        }

        $bar = $this->output->createProgressBar($total);

        $query->chunkById((int) $this->option('chunk'), function ($curations) use ($bar) {
            foreach ($curations as $curation) {
                $this->restoreFor($curation);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->report($dryRun);

        if ($dryRun) {
            DB::rollBack();
        }

        return self::SUCCESS;
    }

    private function restoreFor(Curation $curation): void
    {
        $statusBefore = (int) $curation->curation_status_id;
        $assertions = $this->gciAssertionsFor($curation);
        $changed = false;

        $rows = DB::table('curation_curation_status')
            ->where('curation_id', $curation->getKey())
            ->whereRaw("TIME(status_date) = '00:00:00'")
            ->orderBy('id')
            ->get();

        // Times are applied a day at a time, and only when every untimed row that
        // day can be given one. Timing some rows of a day and not others is worse
        // than timing none: the ones left at midnight sort to the front of the day
        // regardless of when they actually happened.
        $resolved = [];
        $unresolvedDays = [];

        foreach ($rows as $row) {
            $day = substr((string) $row->status_date, 0, 10);
            [$timestamp, $source] = $this->timestampFor($row, $assertions);

            if ($timestamp === null) {
                $unresolvedDays[$day] = true;
                continue;
            }

            $resolved[] = [$row, $timestamp, $source, $day];
        }

        foreach ($resolved as [$row, $timestamp, $source, $day]) {
            if (isset($unresolvedDays[$day])) {
                $this->counts['day left whole']++;
                continue;
            }

            try {
                DB::table('curation_curation_status')
                    ->where('id', $row->id)
                    ->update(['status_date' => $timestamp]);
            } catch (QueryException $e) {
                if (!DuplicateKey::matches($e)) {
                    throw $e;
                }

                // Another row already occupies this status at this instant; the
                // truncated row was a duplicate of it.
                $this->counts['collision']++;
                continue;
            }

            $this->counts[$source]++;
            $changed = true;

            if (count($this->samples) < 10) {
                $this->samples[] = [
                    $curation->id,
                    $curation->gene_symbol,
                    $day,
                    $timestamp,
                    $source,
                ];
            }
        }

        $this->counts['time unknown'] += count(array_keys($unresolvedDays));

        if (!$changed) {
            return;
        }

        ProjectCurationField::run($curation, CurationField::Status);
        $statusAfter = (int) $curation->fresh()->curation_status_id;

        if ($statusAfter !== $statusBefore) {
            $this->moved[] = [$curation->id, $curation->gene_symbol, $statusBefore, $statusAfter];
        }
    }

    /**
     * Status assertions from this curation's stored GCI messages, keyed by
     * status id and calendar day.
     *
     * @return array<string, string>
     */
    private function gciAssertionsFor(Curation $curation): array
    {
        if (!$curation->gdm_uuid) {
            return [];
        }

        $assertions = [];

        $messages = IncomingStreamMessage::where('gdm_uuid', $curation->gdm_uuid)->get();

        foreach ($messages as $stored) {
            $message = new GciMessage($stored->payload);

            if (!$message->hasStatus()) {
                continue;
            }

            try {
                $statusId = $this->statusMap->get($message->status)->id;
            } catch (GciSyncException $e) {
                continue;
            }

            $semanticDate = $message->statusDate;
            $emitted = $message->messageDate;

            if (!$semanticDate || !$emitted) {
                continue;
            }

            // The time comes from when the message was emitted, not from
            // status.date. GCI fills status.date with a synthetic fixed time for
            // approved messages (16:00:00Z), which sorts after the published
            // message that really followed it. Emission times are ordered.
            //
            // Only a message emitted on the same day as the row can supply its
            // time. A backdated status -- approved in 2011, announced in 2022 --
            // tells us the day and nothing more, and the row stays at midnight.
            if (!$emitted->isSameDay($semanticDate)) {
                continue;
            }

            // The last assertion of the day, not the first. A status asserted twice
            // in one day has only one row -- the duplicate was dropped by the old
            // dedup -- and that row has to stand for where the curation ended up,
            // or a status that recurred after another one appears to precede it.
            $key = $statusId.'|'.$semanticDate->toDateString();
            $candidate = $emitted->format('Y-m-d H:i:s');

            if (!isset($assertions[$key]) || $candidate > $assertions[$key]) {
                $assertions[$key] = $candidate;
            }
        }

        return $assertions;
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function timestampFor($row, array $assertions): array
    {
        $key = $row->curation_status_id.'|'.substr((string) $row->status_date, 0, 10);

        if (isset($assertions[$key])) {
            return [$assertions[$key], 'gci message'];
        }

        if ($row->created_at
            && substr((string) $row->created_at, 0, 10) === substr((string) $row->status_date, 0, 10)
            && substr((string) $row->created_at, 11) !== '00:00:00'
        ) {
            return [(string) $row->created_at, 'row write time'];
        }

        return [null, 'time unknown'];
    }

    private function query()
    {
        $query = Curation::withTrashed()->whereExists(
            fn ($q) => $q->from('curation_curation_status as ccs')
                ->whereColumn('ccs.curation_id', 'curations.id')
                ->whereRaw("TIME(ccs.status_date) = '00:00:00'")
        );

        if (!$this->argument('curation')) {
            return $query;
        }

        $curation = Curation::findByAnyId($this->argument('curation'));

        if (!$curation) {
            $this->error('No curation found for "'.$this->argument('curation').'".');

            return null;
        }

        return $query->whereKey($curation->getKey());
    }

    private function report(bool $dryRun): void
    {
        $verb = $dryRun ? 'would be' : 'were';
        $recovered = $this->counts['gci message'] + $this->counts['row write time'];

        $this->info($recovered.' status row(s) '.$verb.' given a time:');
        $this->table(
            ['time taken from', 'rows'],
            [
                ['gci message', $this->counts['gci message']],
                ['row write time', $this->counts['row write time']],
                ['left at midnight (time unknown)', $this->counts['time unknown']],
                ['left alone, day had a row we could not time', $this->counts['day left whole']],
                ['skipped, duplicate of an existing row', $this->counts['collision']],
            ]
        );

        if ($this->samples) {
            $this->newLine();
            $this->line('Sample:');
            $this->table(['curation', 'gene', 'was', 'becomes', 'time taken from'], $this->samples);
        }

        $this->newLine();

        if (empty($this->moved)) {
            $this->info('No curation changed its current status.');

            return;
        }

        // Recovering times can legitimately reorder rows that shared a date, which
        // is the point -- but it is a visible change and belongs in the report.
        $this->warn(count($this->moved).' curation(s) '.($dryRun ? 'would change' : 'changed')
            .' current status once their rows could be ordered:');
        $this->table(['curation', 'gene', 'was', 'becomes'], array_slice($this->moved, 0, 25));

        if (count($this->moved) > 25) {
            $this->line('... and '.(count($this->moved) - 25).' more.');
        }
    }
}
