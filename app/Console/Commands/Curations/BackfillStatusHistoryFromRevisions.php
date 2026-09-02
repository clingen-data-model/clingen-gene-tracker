<?php

namespace App\Console\Commands\Curations;

use App\Actions\Curations\ProjectCurationField;
use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\CurationStatus;
use App\Curations\CurationField;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repairs status history that has rows missing, using the `revisions` table.
 *
 * Some curations carry a curation_status_id that their own history cannot
 * account for -- the pointer says Published while the only history row is the
 * Uploaded one written at creation. The status was really changed; the history
 * row is gone. Rebuilding projections for those curations would take the pointer
 * at its word only if history supports it, so it would demote them instead.
 *
 * Revisionable logged every curation_status_id change, so the transitions are
 * recoverable even though the history rows are not. This puts them back, after
 * which curations:rebuild-projections has something truthful to work from.
 *
 * Deliberately narrow: only curations whose derived status disagrees with their
 * stored one. Of the 13k status revisions in the table, the overwhelming majority
 * correspond to changes that did write history, and replaying those would add
 * rows dated when the pointer was written rather than when the status changed.
 */
class BackfillStatusHistoryFromRevisions extends Command
{
    protected $signature = 'curations:backfill-status-history-from-revisions
                            {curation? : Restrict to one curation, by any of its ids}
                            {--dry-run : Report what would be recorded without writing}';

    protected $description = 'Recover missing curation status history from the revisions table';

    /**
     * Migration 2021_05_17_190805 rewrote every curation_status_id in one pass, so
     * revisions stamped that day record when the pointer was rewritten, not when
     * the status changed. Rows dated from it are flagged for review.
     */
    private const POINTER_MIGRATION_DATE = '2021-05-21';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $candidates = $this->candidates();

        if ($candidates->isEmpty()) {
            $this->info('No curations have a stored status their history cannot account for.');

            return self::SUCCESS;
        }

        $this->info($candidates->count().' curation(s) with status history to recover.');

        // A dry run does the real work and rolls it back, so what it reports is
        // exactly what would happen -- including the rows the dedup rules suppress.
        if ($dryRun) {
            DB::beginTransaction();
        }

        $recorded = [];
        $unrepairable = [];

        // Recording runs the projector, which sets curation_status_id from the
        // history it just gained. Comparing afterwards would therefore always agree.
        // What matters is whether the status a person last set still stands.
        $statusBefore = $candidates->mapWithKeys(
            fn ($curation) => [$curation->id => (int) $curation->curation_status_id]
        );

        foreach ($candidates as $curation) {
            $transitions = $this->transitions($curation);

            if ($transitions->isEmpty()) {
                $unrepairable[] = [$curation->id, $curation->gene_symbol, $curation->curation_status_id, 'no status revisions'];
                continue;
            }

            foreach ($transitions as $revision) {
                $status = CurationStatus::find((int) $revision->new_value);

                if (!$status) {
                    continue;
                }

                $wrote = RecordCurationFieldEvent::run(
                    $curation,
                    CurationField::Status,
                    $status->id,
                    $revision->created_at,
                    'revision-backfill',
                    'revision:'.$revision->id
                );

                if ($wrote) {
                    $recorded[] = [
                        $curation->id,
                        $curation->gene_symbol,
                        $status->name,
                        substr((string) $revision->created_at, 0, 10),
                        $revision->user_id ?: 'system',
                        $this->isMigrationArtifact($revision) ? 'REVIEW: migration date' : '',
                    ];
                }
            }
        }

        $this->report($recorded, $unrepairable, $candidates, $statusBefore, $dryRun);

        if ($dryRun) {
            DB::rollBack();
        }

        return self::SUCCESS;
    }

    /**
     * Curations whose stored status their history cannot account for.
     */
    private function candidates()
    {
        $query = Curation::withTrashed();

        if ($this->argument('curation')) {
            $curation = Curation::findByAnyId($this->argument('curation'));

            if (!$curation) {
                $this->error('No curation found for "'.$this->argument('curation').'".');

                return collect();
            }

            $query->whereKey($curation->getKey());
        }

        return $query->get()->filter(function ($curation) {
            $derived = ProjectCurationField::derivedValue($curation, CurationField::Status);

            return $derived !== null && $derived !== (int) $curation->curation_status_id;
        })->values();
    }

    private function transitions(Curation $curation)
    {
        return collect(DB::table('revisions')
            ->where('revisionable_type', Curation::class)
            ->where('revisionable_id', $curation->getKey())
            ->where('key', 'curation_status_id')
            ->whereNotNull('new_value')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get());
    }

    private function statusName(?int $id): string
    {
        return $id ? (CurationStatus::find($id)->name ?? (string) $id) : 'none';
    }

    private function isMigrationArtifact($revision): bool
    {
        return $revision->user_id === null
            && substr((string) $revision->created_at, 0, 10) === self::POINTER_MIGRATION_DATE;
    }

    private function report(array $recorded, array $unrepairable, $candidates, $statusBefore, bool $dryRun): void
    {
        if ($recorded) {
            $this->newLine();
            $this->info(count($recorded).' history row(s) '.($dryRun ? 'would be' : 'were').' recovered:');
            $this->table(['curation', 'gene', 'status', 'date', 'changed by', 'note'], $recorded);
        }

        if ($unrepairable) {
            $this->newLine();
            $this->warn(count($unrepairable).' curation(s) cannot be repaired from revisions:');
            $this->table(['curation', 'gene', 'stored status', 'reason'], $unrepairable);
        }

        // Two revisions on the same day collapse to one date, and the projector then
        // breaks the tie on workflow rank rather than on which came first. Where that
        // lands somewhere other than the status a person last set, say so.
        $moved = $candidates->map(function ($curation) use ($statusBefore) {
            $now = (int) $curation->fresh()->curation_status_id;
            $was = $statusBefore[$curation->id];

            return $now === $was ? null : [
                $curation->id,
                $curation->gene_symbol,
                $this->statusName($was),
                $this->statusName($now),
            ];
        })->filter()->values();

        $this->newLine();

        if ($moved->isEmpty()) {
            $this->info('Every candidate kept the status it had; history now accounts for it.');

            if (!$dryRun) {
                $this->line('Run curations:rebuild-projections next.');
            }

            return;
        }

        $this->warn($moved->count().' curation(s) '.($dryRun ? 'would change' : 'changed')
            .' status as a result -- review these:');
        $this->table(['curation', 'gene', 'was', 'becomes'], $moved->all());
    }
}
