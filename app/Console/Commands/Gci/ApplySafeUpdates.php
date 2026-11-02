<?php

namespace App\Console\Commands\Gci;

use App\Services\GciReconciliation\{PayloadReader, SafeUpdates, Selection};
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplySafeUpdates extends Command
{
    protected $signature = 'gci:apply-safe-updates
        {--apply : Modify GT; otherwise read-only dry-run}
        {--run= : Select a reconciliation UUID if multiple runs exist}
        {--id=* : Restrict to explicit reconciliation row IDs}
        {--user= : Optional GT user ID for revision and execution attribution}';

    protected $description = 'Dry-run or apply clean GCI disease/MoI corrections using the normal GT update path';

    public function handle(Selection $selection, SafeUpdates $updates): int
    {
        $previousUser = Auth::user();

        try {
            $actor = $this->option('user') ? User::findOrFail($this->option('user')) : null;

            if ($actor) {
                Auth::setUser($actor);
            }

            $rows = $selection->query($this->option('run'), $this->option('id'))->get();
            $inspection = $updates->inspect($rows);
            $plans = collect($inspection['plans']);
            $excluded = collect($inspection['excluded']);

            /*
             * These records passed the safe identity checks but require no GT disease/MoI change because GT already matches GCI.
             */
            $alreadyMatchedIds = $excluded->where('reason', 'No disease/MoI discrepancy in the original snapshot.')->pluck('id');

            $alreadyMatched = $rows->whereIn('id', $alreadyMatchedIds);
            $alreadyMatchedHas = $alreadyMatched->where('source_type', PayloadReader::HAS);
            $alreadyMatchedNo = $alreadyMatched->where('source_type', PayloadReader::NO);
            $this->table(['Category', 'Selected'], [
                [
                    PayloadReader::HAS,
                    $plans->where('source_type', PayloadReader::HAS)->count(),
                ],
                [
                    PayloadReader::NO,
                    $plans->where('source_type', PayloadReader::NO)->count(),
                ],
                ['Total GT updates', $plans->count()],
                [
                    'Disease only',
                    $plans
                        ->filter(fn ($p) =>
                            isset($p['changes']['mondo_id'])
                            && !isset($p['changes']['moi_id'])
                        )
                        ->count(),
                ],
                [
                    'MoI only',
                    $plans
                        ->filter(fn ($p) =>
                            isset($p['changes']['moi_id'])
                            && !isset($p['changes']['mondo_id'])
                        )
                        ->count(),
                ],
                [
                    'Disease + MoI',
                    $plans->filter(fn ($p) => count($p['changes']) === 2)->count(),
                ],
                [
                    'Already matched HAS → NO_ACTION',
                    $alreadyMatchedHas->count(),
                ],
                [
                    'Already matched NO → UPDATE_GCI_PRECURATION_ID',
                    $alreadyMatchedNo->count(),
                ],
                [
                    'Other excluded',
                    $excluded->count() - $alreadyMatched->count(),
                ],
            ]);

            if ($excluded->isNotEmpty()) {
                $this->table(
                    ['Exclusion reason', 'Count'],
                    $excluded
                        ->countBy('reason')
                        ->map(fn ($count, $reason) => [$reason, $count])
                        ->values()
                        ->all()
                );
            }

            $mappingErrors = $excluded->filter(fn ($e) => str_starts_with($e['reason'], 'MONDO mapping') || str_starts_with($e['reason'], 'MoI mapping'));
            if ($mappingErrors->isNotEmpty()) {
                $this->table(['Row ID', 'Mapping problem'], $mappingErrors->map(fn ($e) => [$e['id'], $e['reason']])->all());
            }

            if (!$this->option('apply')) {
                $this->info('NO DATABASE CHANGES MADE. Use --apply to apply the selected safe updates and mark safe records reviewed.');
                return self::SUCCESS;
            }

            /*
             * Records that already match do not need a GT update,
             * but they also do not need human review anymore.
             */
            if ($alreadyMatchedHas->isNotEmpty()) {
                DB::table('gci_gt_reconciliations')
                    ->whereIn('id', $alreadyMatchedHas->pluck('id'))
                    ->update([
                        'review_status' => 'REVIEWED',
                        'resolution' => 'NO_ACTION',
                        'review_notes' => 'GCI and GT already match. No GT update required.',
                        'reviewed_by' => $actor?->id,
                        'reviewed_at' => now(),
                        'execution_status' => 'NO_ACTION',
                        'updated_at' => now(),
                    ]);
            }

            if ($alreadyMatchedNo->isNotEmpty()) {
                DB::table('gci_gt_reconciliations')
                    ->whereIn('id', $alreadyMatchedNo->pluck('id'))
                    ->update([
                        'review_status' => 'REVIEWED',
                        'resolution' => 'UPDATE_GCI_PRECURATION_ID',
                        'review_notes' => 'GCI UUID uniquely matches GT and all compared GT fields already match. GT requires no update; GCI needs the GT precuration ID.',
                        'reviewed_by' => $actor?->id,
                        'reviewed_at' => now(),
                        'execution_status' => 'NO_ACTION',
                        'updated_at' => now(),
                    ]);
            }

            /*
             * Mapping problems are still genuine failures and must not
             * be marked reviewed/resolved.
             */
            foreach ($mappingErrors as $error) {
                $updates->recordFailure(
                    $error['id'],
                    $actor?->id,
                    $error['reason']
                );
            }

            $failed = $mappingErrors->count();

            /*
             * Apply actual disease/MoI changes through SafeUpdates,
             * which preserves the normal GT update/event/DX path.
             */
            foreach ($plans as $plan) {
                $result = $updates->apply($plan['id'], $actor?->id);
                $this->line( 'Row ' .$result['id'] .': ' .$result['status'] .(isset($result['reason']) ? ' - '.$result['reason'] : ''));

                if ($result['status'] === 'FAILED') {
                    $failed++;

                    continue;
                }

                if ($result['status'] === 'APPLIED') {
                    DB::table('gci_gt_reconciliations')
                        ->where('id', $result['id'])
                        ->update([
                            'review_status' => 'REVIEWED',
                            'resolution' => 'UPDATE_GT_DISEASE_MOI',
                            'review_notes' => 'Safe GCI disease/MoI synchronization applied to GT.',
                            'reviewed_by' => $actor?->id,
                            'reviewed_at' => now(),
                            'execution_status' => 'APPLIED',
                            'updated_at' => now(),
                        ]);
                }
            }

            $this->newLine();
            $this->info(
                'Marked '.$alreadyMatchedHas->count()
                .' HAS_PRECURATION_ID records reviewed/no-action.'
            );

            $this->info(
                'Marked '.$alreadyMatchedNo->count()
                .' NO_PRECURATION_ID records reviewed for GCI precuration-ID update.'
            );

            return $failed
                ? self::FAILURE
                : self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetGuards();
            }
        }
    }
}