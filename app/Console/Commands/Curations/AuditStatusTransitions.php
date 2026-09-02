<?php

namespace App\Console\Commands\Curations;

use App\Curation;
use App\CurationStatus;
use App\Curations\StatusTransitions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Measures recorded status history against the state machine in
 * App\Curations\StatusTransitions.
 *
 * Reports rather than repairs. A transition outside the graph can mean the graph
 * is incomplete, or that history is missing the steps in between -- the two look
 * identical from here, so the judgement stays with the reader.
 */
class AuditStatusTransitions extends Command
{
    protected $signature = 'curations:audit-status-transitions
                            {curation? : Show the full sequence for one curation, by any of its ids}
                            {--ordering=rank : How to sequence rows sharing a date: rank or insertion}
                            {--limit=40 : Rows of transition detail to show}
                            {--with-trashed}';

    protected $description = 'Report curation status history that does not fit the status state machine';

    private array $statusNames = [];

    public function handle(): int
    {
        if (!in_array($this->option('ordering'), ['rank', 'insertion'], true)) {
            $this->error('--ordering must be "rank" or "insertion".');

            return self::FAILURE;
        }

        $this->statusNames = CurationStatus::pluck('name', 'id')->all();

        if ($this->argument('curation')) {
            return $this->auditOne();
        }

        return $this->auditAll();
    }

    private function auditOne(): int
    {
        $curation = Curation::withTrashed()->where(function ($q) {
            $q->where('id', $this->argument('curation'))
              ->orWhere('uuid', $this->argument('curation'))
              ->orWhere('gdm_uuid', $this->argument('curation'));
        })->first();

        if (!$curation) {
            $this->error('No curation found for "'.$this->argument('curation').'".');

            return self::FAILURE;
        }

        $sequence = $this->sequenceFor($curation->id);

        $this->info('Curation '.$curation->id.' ('.$curation->gene_symbol.'), created '
            .optional($curation->created_at)->toDateString());

        if (empty($sequence)) {
            $this->warn('No status history.');

            return self::SUCCESS;
        }

        $rows = [];
        $previous = null;

        foreach ($sequence as $row) {
            $rows[] = [
                $this->name($row->curation_status_id),
                substr((string) $row->status_date, 0, 10),
                $row->source,
                $previous === null
                    ? ($row->curation_status_id == StatusTransitions::INITIAL ? 'start' : 'STARTS OFF-GRAPH')
                    : $this->verdict($previous, (int) $row->curation_status_id),
            ];
            $previous = (int) $row->curation_status_id;
        }

        $this->table(['status', 'date', 'source', 'verdict'], $rows);

        return self::SUCCESS;
    }

    private function auditAll(): int
    {
        $sequences = $this->allSequences();

        $verdicts = ['legal' => 0, 'repeat' => 0, 'not in graph' => 0];
        $pairs = [];
        $starts = [];
        $offGraphCurations = [];

        foreach ($sequences as $curationId => $rows) {
            $first = (int) $rows[0]->curation_status_id;
            $starts[$first] = ($starts[$first] ?? 0) + 1;

            $previous = null;
            $offGraph = 0;

            foreach ($rows as $row) {
                $status = (int) $row->curation_status_id;

                if ($previous !== null) {
                    $verdict = $this->verdict($previous, $status);
                    $verdicts[$verdict]++;
                    $key = $previous.'->'.$status;
                    $pairs[$key] = $pairs[$key] ?? ['from' => $previous, 'to' => $status, 'n' => 0, 'verdict' => $verdict];
                    $pairs[$key]['n']++;

                    if ($verdict === 'not in graph') {
                        $offGraph++;
                    }
                }

                $previous = $status;
            }

            if ($offGraph > 0) {
                $offGraphCurations[$curationId] = $offGraph;
            }
        }

        $this->summary($sequences, $verdicts, $offGraphCurations);
        $this->transitionTable($pairs);
        $this->startTable($starts, count($sequences));
        $this->missingInitial();

        return self::SUCCESS;
    }

    private function verdict(int $from, int $to): string
    {
        if ($from === $to) {
            return 'repeat';
        }

        return StatusTransitions::isAllowed($from, $to) ? 'legal' : 'not in graph';
    }

    private function summary($sequences, array $verdicts, array $offGraphCurations): void
    {
        $total = array_sum($verdicts);

        $this->info('Audited '.count($sequences).' curation(s), '.$total.' transition(s), sequenced by '
            .$this->option('ordering').' order.');
        $this->newLine();
        $this->table(
            ['verdict', 'transitions', '%'],
            collect($verdicts)->map(fn ($n, $v) => [
                $v, $n, $total ? round(100 * $n / $total, 1).'%' : '-',
            ])->values()->all()
        );
        $this->line(count($offGraphCurations).' curation(s) have at least one transition outside the graph.');
    }

    private function transitionTable(array $pairs): void
    {
        usort($pairs, fn ($a, $b) => $b['n'] <=> $a['n']);

        $this->newLine();
        $this->info('Transitions observed (most frequent first):');
        $this->table(
            ['from', 'to', 'count', 'verdict'],
            collect($pairs)->take((int) $this->option('limit'))->map(fn ($p) => [
                $this->name($p['from']),
                $this->name($p['to']),
                $p['n'],
                $p['verdict'] === 'not in graph' ? 'NOT IN GRAPH' : $p['verdict'],
            ])->all()
        );

        if (count($pairs) > (int) $this->option('limit')) {
            $this->line((count($pairs) - (int) $this->option('limit')).' further transition type(s) not shown; raise --limit.');
        }
    }

    private function startTable(array $starts, int $curations): void
    {
        ksort($starts);
        $expected = $starts[StatusTransitions::INITIAL] ?? 0;

        $this->newLine();
        $this->info('First recorded status ('.$expected.' of '.$curations.' start at '
            .$this->name(StatusTransitions::INITIAL).'):');
        $this->table(
            ['status', 'curations'],
            collect($starts)->map(fn ($n, $id) => [$this->name((int) $id), $n])->values()->all()
        );
    }

    /**
     * A curation whose history never records the initial status at all. These are
     * invisible to the transition counts above, because the sequence simply starts
     * somewhere else.
     */
    private function missingInitial(): void
    {
        $missing = DB::table('curations as c')
            ->when(!$this->option('with-trashed'), fn ($q) => $q->whereNull('c.deleted_at'))
            ->whereExists(fn ($q) => $q->from('curation_curation_status as y')
                ->whereColumn('y.curation_id', 'c.id'))
            ->whereNotExists(fn ($q) => $q->from('curation_curation_status as x')
                ->whereColumn('x.curation_id', 'c.id')
                ->where('x.curation_status_id', StatusTransitions::INITIAL))
            ->count();

        $this->newLine();
        $this->line($missing.' curation(s) have status history but no '
            .$this->name(StatusTransitions::INITIAL).' row at all.');
    }

    /**
     * @return array<int, array> rows per curation, oldest first
     */
    private function allSequences(): array
    {
        $rows = DB::table('curation_curation_status as ccs')
            ->join('curations as c', 'c.id', '=', 'ccs.curation_id')
            ->when(!$this->option('with-trashed'), fn ($q) => $q->whereNull('c.deleted_at'))
            ->orderBy('ccs.curation_id')
            ->orderBy('ccs.status_date')
            ->orderBy($this->tiebreakColumn())
            ->orderBy('ccs.id')
            ->get(['ccs.curation_id', 'ccs.curation_status_id', 'ccs.status_date', 'ccs.source']);

        $sequences = [];

        foreach ($rows as $row) {
            $sequences[$row->curation_id][] = $row;
        }

        return $sequences;
    }

    private function sequenceFor(int $curationId): array
    {
        return DB::table('curation_curation_status as ccs')
            ->where('ccs.curation_id', $curationId)
            ->orderBy('ccs.status_date')
            ->orderBy($this->tiebreakColumn())
            ->orderBy('ccs.id')
            ->get(['ccs.curation_status_id', 'ccs.status_date', 'ccs.source'])
            ->all();
    }

    private function tiebreakColumn(): string
    {
        return $this->option('ordering') === 'rank' ? 'ccs.curation_status_id' : 'ccs.id';
    }

    private function name(int $id): string
    {
        return $this->statusNames[$id] ?? (string) $id;
    }
}
