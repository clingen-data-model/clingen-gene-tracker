<?php

namespace App\Console\Commands\Curations;

use App\Actions\Curations\ProjectCurationField;
use App\Curation;
use App\Curations\CurationField;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes everything derived from curation field history.
 *
 * The history rows are the source of truth, so any drift in the derived data --
 * a stale curation_status_id, an expert panel interval that was never closed --
 * is repairable by recomputing rather than by another bespoke fix-up command.
 * This replaces curations:order-statuses, curations:set_current_status_id and
 * curations:clean-statuses.
 */
class RebuildProjections extends Command
{
    protected $signature = 'curations:rebuild-projections
                            {curation? : Restrict to one curation, by any of its ids}
                            {--field= : Restrict to one field: status, classification or expert_panel}
                            {--chunk=500}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Recompute current values and ownership intervals from curation field history';

    public function handle(): int
    {
        $fields = $this->fields();

        if ($fields === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $query = $this->query();

        if ($query === null) {
            return self::FAILURE;
        }

        $total = $query->count();
        $this->info(($dryRun ? 'Checking ' : 'Rebuilding ').$total.' curation(s).');

        $bar = $this->output->createProgressBar($total);
        $drift = [];

        config(['curations.replaying' => true]);

        try {
            $query->chunkById((int) $this->option('chunk'), function ($curations) use ($fields, $dryRun, $bar, &$drift) {
                foreach ($curations as $curation) {
                    foreach ($fields as $field) {
                        $difference = $this->difference($curation, $field);

                        if ($difference) {
                            $drift[] = $difference;
                        }

                        if (!$dryRun) {
                            ProjectCurationField::run($curation, $field);
                        }
                    }

                    $bar->advance();
                }
            });
        } finally {
            config(['curations.replaying' => false]);
        }

        $bar->finish();
        $this->newLine(2);
        $this->report($drift, $dryRun);

        return self::SUCCESS;
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
        if (!$this->argument('curation')) {
            return Curation::withTrashed();
        }

        $curation = Curation::findByAnyId($this->argument('curation'));

        if (!$curation) {
            $this->error('No curation found for "'.$this->argument('curation').'".');

            return null;
        }

        return Curation::withTrashed()->whereKey($curation->getKey());
    }

    /**
     * What the projector would change for this curation and field, if anything.
     */
    private function difference(Curation $curation, CurationField $field): ?array
    {
        $column = $field->currentValueColumn();

        if ($column === null) {
            return null;
        }

        $winner = DB::table($field->historyTable())
            ->where('curation_id', $curation->getKey())
            ->orderByDesc($field->dateColumn())
            ->orderByDesc($field->tiebreakColumn())
            ->orderByDesc('id')
            ->value($field->valueColumn());

        if ($winner === null || (int) $winner === (int) $curation->{$column}) {
            return null;
        }

        return [
            'curation' => $curation->getKey(),
            'field' => $field->value,
            'stored' => $curation->{$column},
            'derived' => $winner,
        ];
    }

    private function report(array $drift, bool $dryRun): void
    {
        if (empty($drift)) {
            $this->info('No drift between stored current values and history.');

            return;
        }

        $this->warn(count($drift).' current value(s) '.($dryRun ? 'would be' : 'were').' corrected:');
        $this->table(['curation', 'field', 'stored', 'derived'], $drift);
    }
}
