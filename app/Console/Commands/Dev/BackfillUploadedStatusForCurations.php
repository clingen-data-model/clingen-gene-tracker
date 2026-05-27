<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Curation;
use App\Events\Curation\Updated as CurationUpdated;

class BackfillUploadedStatusForCurations extends Command
{
    protected $signature = 'curations:backfill-uploaded-status
        {--dry-run : Show what would be updated without changing data}
        {--chunk=500 : Number of curations to process per chunk}
        {--limit= : Optional max number of curations to process}
        {--include-deleted : Include soft-deleted curations}
        {--force-current : Set curations.curation_status_id to Uploaded even when it already has another value}
        {--send-dx : Dispatch curation updated events after backfill}';

    protected $description = 'Backfill missing Uploaded status rows for curations that have no status history.';

    public function handle(): int
    {
        $uploadedStatusId = DB::table('curation_statuses')
            ->where('name', 'Uploaded')
            ->value('id');

        if (!$uploadedStatusId) {
            $this->error('Could not find curation status: Uploaded');
            return self::FAILURE;
        }
        $sendDx = (bool) $this->option('send-dx');
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = $sendDx ? min((int) $this->option('chunk'), 25) : (int) $this->option('chunk');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $includeDeleted = (bool) $this->option('include-deleted');
        $forceCurrent = (bool) $this->option('force-current');        

        $baseQuery = DB::table('curations')
            ->select([
                'curations.id',
                'curations.uuid',
                'curations.created_at',
                'curations.curation_status_id',
            ])
            ->whereNotNull('curations.created_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('curation_curation_status')
                    ->whereColumn('curation_curation_status.curation_id', 'curations.id');
            })
            ->orderBy('curations.id');

        if (!$includeDeleted) {
            $baseQuery->whereNull('curations.deleted_at');
        }

        if ($limit) {
            $baseQuery->limit($limit);
        }

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info('No curations found without status history.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} curations without status history.");

        if ($dryRun) {
            $samples = (clone $baseQuery)->limit(20)->get();

            $this->table(
                ['curation_id', 'uuid', 'created_at', 'current_status_id', 'new_status_id', 'new_status_date'],
                $samples->map(function ($curation) use ($uploadedStatusId) {
                    return [
                        $curation->id,
                        $curation->uuid,
                        $curation->created_at,
                        $curation->curation_status_id,
                        $uploadedStatusId,
                        $curation->created_at,
                    ];
                })->toArray()
            );

            $this->warn('Dry run only. No records were changed.');
            return self::SUCCESS;
        }

        $processed = 0;

        $baseQuery->chunkById($chunkSize, function ($curations) use (
            $uploadedStatusId,
            $forceCurrent,
            $sendDx,
            &$processed
        ) {
            $curationIds = $curations->pluck('id')->all();

            DB::transaction(function () use ($curations, $uploadedStatusId, $forceCurrent, &$processed) {
                $now = now();

                $rows = $curations->map(function ($curation) use ($uploadedStatusId, $now) {
                    return [
                        'curation_id' => $curation->id,
                        'curation_status_id' => $uploadedStatusId,
                        'status_date' => $curation->created_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->toArray();

                DB::table('curation_curation_status')->insert($rows);

                $curationIds = $curations->pluck('id');

                $updateCurrentStatusQuery = DB::table('curations')
                    ->whereIn('id', $curationIds);

                if (!$forceCurrent) {
                    $updateCurrentStatusQuery->whereNull('curation_status_id');
                }

                $updateCurrentStatusQuery->update([
                    'curation_status_id' => $uploadedStatusId,
                    'updated_at' => $now,
                ]);

                $processed += $curations->count();

                $this->info("Processed {$processed} curations...");
            });

            if ($sendDx) {
                foreach ($curationIds as $curationId) {
                    $curation = Curation::find($curationId);
                    if (!$curation) { continue; }
                    event(new CurationUpdated($curation));
                    $curation->unsetRelations();
                    unset($curation);
                    gc_collect_cycles();
                }

                $this->info('Dispatched DX update events for this batch.');
            }
        }, 'curations.id', 'id');
        $this->info("Done. Backfilled Uploaded status for {$processed} curations.");

        return self::SUCCESS;
    }
}