<?php

namespace App\Console\Commands\Curations;

use App\Actions\Curations\ProjectCurationField;
use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\Curations\CurationField;
use App\Curations\StatusTransitions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives back the Uploaded row to curations whose history never recorded one.
 *
 * Every curation starts as Uploaded, but a historical bug left some without that
 * row, so their history begins partway through the workflow. FixStatusOrder was
 * meant to address this and could not: it only re-dated an Uploaded row that
 * already existed, and it only looked at curations whose current status was
 * already Uploaded, which none of these are.
 *
 * The date is the earliest moment we can evidence the curation existed. That is
 * usually created_at, but roughly a third of these carry a status dated before
 * the tracker record was made -- curated in the GCI before being uploaded here --
 * and for those created_at would place Uploaded after statuses that precede it.
 */
class ImputeUploadedStatus extends Command
{
    protected $signature = 'curations:impute-uploaded-status
                            {curation? : Restrict to one curation, by any of its ids}
                            {--dry-run : Report what would be recorded without writing}
                            {--chunk=500}';

    protected $description = 'Record the missing Uploaded status for curations whose history never got one';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = $this->query();

        if ($query === null) {
            return self::FAILURE;
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Every curation with status history already has an '
                .$this->initialStatusName().' row.');

            return self::SUCCESS;
        }

        $this->info($total.' curation(s) have status history with no '.$this->initialStatusName().' row.');

        if ($dryRun) {
            DB::beginTransaction();
        }

        $byRule = ['created_at' => 0, 'first recorded status' => 0];
        $samples = [];
        $moved = [];
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);

        $query->chunkById((int) $this->option('chunk'), function ($curations) use (
            &$byRule, &$samples, &$moved, &$skipped, $bar
        ) {
            foreach ($curations as $curation) {
                $statusBefore = (int) $curation->curation_status_id;
                // Recording runs the projector, which also applies any correction
                // that was already outstanding for this curation. Capturing what
                // history said beforehand keeps the two causes apart.
                $derivedBefore = ProjectCurationField::derivedValue($curation, CurationField::Status);
                [$date, $rule] = $this->uploadedDateFor($curation);

                if ($date === null) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $recorded = RecordCurationFieldEvent::run(
                    $curation,
                    CurationField::Status,
                    StatusTransitions::INITIAL,
                    $date,
                    'imputed',
                    'impute-uploaded:'.$curation->id
                );

                if (!$recorded) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $byRule[$rule]++;

                if (count($samples) < 10) {
                    $samples[] = [$curation->id, $curation->gene_symbol, substr((string) $date, 0, 10), $rule];
                }

                $statusAfter = (int) $curation->fresh()->curation_status_id;

                if ($statusAfter !== $statusBefore) {
                    $moved[] = [
                        $curation->id,
                        $curation->gene_symbol,
                        $statusBefore,
                        $statusAfter,
                        $derivedBefore !== null && $derivedBefore !== $statusBefore
                            ? 'drift already pending'
                            : 'CAUSED BY THIS IMPUTATION',
                    ];
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->report($byRule, $samples, $moved, $skipped, $dryRun);

        if ($dryRun) {
            DB::rollBack();
        }

        return self::SUCCESS;
    }

    /**
     * The earliest moment the curation can be evidenced to have existed.
     *
     * @return array{0: ?string, 1: string}
     */
    private function uploadedDateFor(Curation $curation): array
    {
        $firstStatus = DB::table('curation_curation_status')
            ->where('curation_id', $curation->getKey())
            ->min('status_date');

        if ($firstStatus === null) {
            return [null, 'none'];
        }

        $createdAt = optional($curation->created_at)->format('Y-m-d H:i:s');

        // Falling back to the first status leaves Uploaded sharing that date rather
        // than preceding it. That is as far back as the evidence goes; inventing an
        // earlier date would be making something up.
        if ($createdAt === null || $createdAt > $firstStatus) {
            return [$firstStatus, 'first recorded status'];
        }

        return [$createdAt, 'created_at'];
    }

    private function query()
    {
        $query = Curation::withTrashed()
            ->whereExists(fn ($q) => $q->from('curation_curation_status as y')
                ->whereColumn('y.curation_id', 'curations.id'))
            ->whereNotExists(fn ($q) => $q->from('curation_curation_status as x')
                ->whereColumn('x.curation_id', 'curations.id')
                ->where('x.curation_status_id', StatusTransitions::INITIAL));

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

    private function report(array $byRule, array $samples, array $moved, int $skipped, bool $dryRun): void
    {
        $verb = $dryRun ? 'would be' : 'were';

        $this->info(array_sum($byRule).' '.$this->initialStatusName().' row(s) '.$verb.' recorded, dated by:');
        $this->table(
            ['date taken from', 'curations'],
            collect($byRule)->map(fn ($n, $rule) => [$rule, $n])->values()->all()
        );

        if ($samples) {
            $this->newLine();
            $this->line('Sample:');
            $this->table(['curation', 'gene', 'uploaded date', 'date taken from'], $samples);
        }

        if ($skipped) {
            $this->newLine();
            $this->line($skipped.' curation(s) skipped: already recorded, or nothing to anchor a date to.');
        }

        $this->newLine();

        // Imputing at or before the earliest existing row must not disturb which
        // status is current. If it did, something about that curation is unusual.
        if (empty($moved)) {
            $this->info('No curation changed its current status.');

            return;
        }

        $caused = array_values(array_filter($moved, fn ($m) => $m[4] === 'CAUSED BY THIS IMPUTATION'));

        $this->warn(count($moved).' curation(s) '.($dryRun ? 'would change' : 'changed').' current status:');
        $this->table(['curation', 'gene', 'was', 'becomes', 'cause'], $moved);

        if (empty($caused)) {
            $this->line('None of these are caused by the imputation itself -- each already had a '
                .'correction outstanding that curations:rebuild-projections would apply anyway.');
        }
    }

    private function initialStatusName(): string
    {
        return \App\CurationStatus::find(StatusTransitions::INITIAL)->name ?? 'initial';
    }
}
