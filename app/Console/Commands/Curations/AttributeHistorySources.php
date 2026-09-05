<?php

namespace App\Console\Commands\Curations;

use App\Curation;
use App\Curations\CurationField;
use App\Curations\DuplicateKey;
use App\DataExchange\Maps\GciStatusMap;
use App\Exceptions\GciSyncException;
use App\ExpertPanel;
use App\Gci\GciClassificationMap;
use App\Gci\GciMessage;
use App\IncomingStreamMessage;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Names the origin of history rows that predate source keys.
 *
 * The schema migration gave every existing row `source = 'backfill'` and a key
 * derived from its own id. That is unique and honest, but it says only "this row
 * was here already". Where the evidence is unambiguous a row can be given the
 * source and key its live writer would have produced, which makes replaying the
 * event that first created it a no-op on the source-key index rather than on the
 * value/date index behind it.
 *
 * Two kinds of evidence, and nothing else:
 *
 *  - a stored GCI message asserting this value at this exact instant. The row's
 *    date has to match to the second, so this is worth running only after
 *    curations:restore-status-timestamps has put the time of day back -- before
 *    that, every legacy status row sits at midnight and matches nothing;
 *  - a revision recording this value with a user_id. Revisionable's
 *    getSystemUserId() returns null on every queue and console path, so a user
 *    is positive evidence that a human set the value in the UI.
 *
 * Anything else keeps `backfill`, which then means "we looked and could not
 * tell" rather than "we never asked". Values and dates are never touched, so
 * nothing here can change a projection.
 */
class AttributeHistorySources extends Command
{
    protected $signature = 'curations:attribute-history-sources
                            {curation? : Restrict to one curation, by any of its ids}
                            {--field= : Restrict to status, classification or expert_panel}
                            {--dry-run : Report what would change without writing}
                            {--chunk=200}';

    protected $description = 'Replace placeholder backfill source keys with the real origin of each history row';

    private GciStatusMap $statusMap;

    private GciClassificationMap $classificationMap;

    private array $counts = [
        'gci' => 0,
        'ui' => 0,
        'ambiguous' => 0,
        'no evidence' => 0,
        'collision' => 0,
    ];

    private array $samples = [];

    public function handle(GciStatusMap $statusMap, GciClassificationMap $classificationMap): int
    {
        $this->statusMap = $statusMap;
        $this->classificationMap = $classificationMap;

        $fields = $this->fields();
        $query = $this->query();

        if ($fields === null || $query === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No history rows are still carrying a placeholder source key.');

            return self::SUCCESS;
        }

        $this->info($total.' curation(s) have history rows to attribute.');

        if ($dryRun) {
            DB::beginTransaction();
        }

        $bar = $this->output->createProgressBar($total);

        $query->chunkById((int) $this->option('chunk'), function ($curations) use ($fields, $bar) {
            foreach ($curations as $curation) {
                foreach ($fields as $field) {
                    $this->attributeFor($curation, $field);
                }

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

    private function attributeFor(Curation $curation, CurationField $field): void
    {
        $rows = DB::table($field->historyTable())
            ->where('curation_id', $curation->getKey())
            ->where('source', 'backfill')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $gci = $this->gciAssertionsFor($curation, $field);
        $ui = $this->uiAssertionsFor($curation, $field);

        $attributions = [];
        $claims = [];

        foreach ($rows as $row) {
            $value = (int) $row->{$field->valueColumn()};
            $date = (string) $row->{$field->dateColumn()};

            [$source, $key] = $this->attributionFor($field, $value, $date, $gci, $ui);

            if ($key === null) {
                continue;
            }

            $attributions[] = [$row, $source, $key];
            $claims[$key] = ($claims[$key] ?? 0) + 1;
        }

        foreach ($attributions as [$row, $source, $key]) {
            // One message can assert a value at its status date and at its emission
            // time both, and two rows can then claim the same key. Which of them
            // the message really wrote is a guess, so neither is attributed.
            if ($claims[$key] > 1) {
                $this->counts['ambiguous']++;

                continue;
            }

            $this->rewrite($field, $row, $source, $key, $curation);
        }
    }

    /**
     * GCI is preferred over a revision: it matches an external event to the
     * second, where a revision only puts a user on the day.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function attributionFor(
        CurationField $field,
        int $value,
        string $date,
        array $gci,
        array $ui
    ): array {
        $keys = array_keys($gci[$value.'|'.substr($date, 0, 19)] ?? []);

        if (count($keys) === 1) {
            return ['gci', $keys[0]];
        }

        if (count($keys) > 1) {
            // Two messages assert the same value at the same instant. Either could
            // have written the row and picking one is a guess.
            $this->counts['ambiguous']++;

            return [null, null];
        }

        if (isset($ui[$value.'|'.substr($date, 0, 10)])) {
            return ['ui', 'ui:'.$field->value.':'.substr($date, 0, 10).':'.$value];
        }

        $this->counts['no evidence']++;

        return [null, null];
    }

    private function rewrite(CurationField $field, $row, string $source, string $key, Curation $curation): void
    {
        try {
            DB::table($field->historyTable())
                ->where('id', $row->id)
                ->update(['source' => $source, 'source_event_key' => $key]);
        } catch (QueryException $e) {
            if (!DuplicateKey::matches($e)) {
                throw $e;
            }

            // Another row of this curation already carries the key. Both rows
            // describe the same event, and the one already keyed came first.
            $this->counts['collision']++;

            return;
        }

        $this->counts[$source]++;

        if (count($this->samples) < 10) {
            $this->samples[] = [
                $curation->id,
                $curation->gene_symbol,
                $field->value,
                substr((string) $row->{$field->dateColumn()}, 0, 19),
                $source,
                $key,
            ];
        }
    }

    /**
     * Every value a stored GCI message asserts for this field, keyed by value and
     * instant. The instants are the ones a writer could have left on the row: the
     * date the live writer records, plus, for status, the emission time that
     * curations:restore-status-timestamps writes.
     *
     * @return array<string, array<string, bool>> "value|instant" => source keys
     */
    private function gciAssertionsFor(Curation $curation, CurationField $field): array
    {
        if (!$curation->gdm_uuid) {
            return [];
        }

        $assertions = [];

        foreach (IncomingStreamMessage::where('gdm_uuid', $curation->gdm_uuid)->get() as $stored) {
            $message = new GciMessage($stored->payload);
            $value = $this->assertedValue($message, $field);

            if ($value === null) {
                continue;
            }

            foreach ($this->instantsFor($message, $field) as $instant) {
                $assertions[$value.'|'.$instant][$message->sourceKey] = true;
            }
        }

        return $assertions;
    }

    private function assertedValue(GciMessage $message, CurationField $field): ?int
    {
        try {
            return match ($field) {
                CurationField::Status => $message->hasStatus()
                    ? $this->statusMap->get($message->status)->id
                    : null,
                CurationField::Classification => $this->assertedClassification($message),
                CurationField::ExpertPanel => $this->assertedExpertPanel($message),
            };
        } catch (GciSyncException $e) {
            // A status or evidence level this version of the app no longer maps.
            return null;
        }
    }

    private function assertedClassification(GciMessage $message): ?int
    {
        if (!isset($message->getPayload()->gene_validity_evidence_level->evidence_level)) {
            return null;
        }

        return $this->classificationMap->get($message->classification)->id;
    }

    private function assertedExpertPanel(GciMessage $message): ?int
    {
        if (!$message->isGdmTransfer() || !isset($message->content->transfer_to->gcep_id)) {
            return null;
        }

        $expertPanel = ExpertPanel::findByAffiliationId($message->content->transfer_to->gcep_id);

        return $expertPanel ? $expertPanel->id : null;
    }

    /**
     * @return string[]
     */
    private function instantsFor(GciMessage $message, CurationField $field): array
    {
        $instants = [];

        if ($field === CurationField::ExpertPanel) {
            // A transfer is recorded at the message date; there is no status.date
            // involved, and nothing restores these timestamps.
            $instants[] = $message->messageDate;
        } else {
            $instants[] = $message->statusDate;
        }

        if ($field === CurationField::Status) {
            // Restored rows carry the emission time rather than status.date, which
            // GCI fills with a synthetic fixed time for approved messages.
            $instants[] = $message->messageDate;
        }

        return array_values(array_unique(array_map(
            fn ($instant) => $instant ? $instant->format('Y-m-d H:i:s') : null,
            array_filter($instants)
        )));
    }

    /**
     * Values a human set through the UI, by value and day.
     *
     * Revisionable records a user only when one is authenticated, which on the
     * queue and console paths -- every GCI path -- is never. Classification has no
     * column on curations, so no revision can speak for it.
     *
     * @return array<string, bool>
     */
    private function uiAssertionsFor(Curation $curation, CurationField $field): array
    {
        $column = $field->currentValueColumn();

        if ($column === null) {
            return [];
        }

        $revisions = DB::table('revisions')
            ->where('revisionable_type', Curation::class)
            ->where('revisionable_id', $curation->getKey())
            ->where('key', $column)
            ->whereNotNull('user_id')
            ->select('new_value', 'created_at')
            ->get();

        $assertions = [];

        foreach ($revisions as $revision) {
            if ($revision->new_value === null || $revision->new_value === '') {
                continue;
            }

            $assertions[(int) $revision->new_value.'|'.substr((string) $revision->created_at, 0, 10)] = true;
        }

        return $assertions;
    }

    /** @return CurationField[]|null */
    private function fields(): ?array
    {
        if (!$this->option('field')) {
            return CurationField::cases();
        }

        $field = CurationField::tryFrom($this->option('field'));

        if (!$field) {
            $this->error('Unknown field "'.$this->option('field').'". Expected one of: '
                .implode(', ', array_column(CurationField::cases(), 'value')));

            return null;
        }

        return [$field];
    }

    private function query()
    {
        $query = Curation::withTrashed()->where(function ($outer) {
            foreach (CurationField::cases() as $field) {
                $outer->orWhereExists(
                    fn ($q) => $q->from($field->historyTable())
                        ->whereColumn($field->historyTable().'.curation_id', 'curations.id')
                        ->where($field->historyTable().'.source', 'backfill')
                );
            }
        });

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
        $attributed = $this->counts['gci'] + $this->counts['ui'];

        $this->info($attributed.' history row(s) '.$verb.' attributed:');
        $this->table(
            ['outcome', 'rows'],
            [
                ['gci, matched a stored message', $this->counts['gci']],
                ['ui, matched a revision with a user', $this->counts['ui']],
                ['left as backfill, no evidence', $this->counts['no evidence']],
                ['left as backfill, more than one message matched', $this->counts['ambiguous']],
                ['skipped, key already taken by another row', $this->counts['collision']],
            ]
        );

        if ($this->samples) {
            $this->newLine();
            $this->line('Sample:');
            $this->table(['curation', 'gene', 'field', 'dated', 'source', 'key'], $this->samples);
        }

        // Matching is to the second, so a row still truncated to midnight cannot
        // match a message. Say so only when there really are such rows left --
        // after a successful run, nothing matching is the expected steady state.
        $untimed = DB::table(CurationField::Status->historyTable())
            ->where('source', 'backfill')
            ->whereRaw("TIME(status_date) = '00:00:00'")
            ->count();

        if ($untimed > 0) {
            $this->newLine();
            $this->warn($untimed.' status row(s) are still at midnight and cannot match a message. '
                .'Run curations:restore-status-timestamps, then run this again.');
        }
    }
}
