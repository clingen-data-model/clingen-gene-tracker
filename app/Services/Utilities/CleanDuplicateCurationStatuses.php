<?php

namespace App\Services\Utilities;

use Illuminate\Support\Facades\DB;

class CleanDuplicateCurationStatuses
{
    protected $backupPath;
    protected $table = 'curation_curation_status';

    public function __construct()
    {
        $this->backupPath = storage_path('database/migrated_data/duplicate_curation_curation_statuses.csv');
    }

    public function clean()
    {
        $this->backupDuplicateForRoleback();
        $this->deleteDuplicates();
    }

    public function restore()
    {
        $this->restoreDeletedDuplicates();
    }

    private function deleteDuplicates()
    {
        DB::delete('DELETE tbl2.* FROM '.$this->table.' tbl1
                        JOIN '.$this->table.' tbl2 ON tbl1.id < tbl2.id
                    WHERE tbl1.curation_id = tbl2.curation_id
                        AND tbl1.`curation_status_id` = tbl2.`curation_status_id`
                        AND tbl1.status_date = tbl2.`status_date`'
                    );
    }

    private function backupDuplicateForRoleback()
    {
        $query = DB::table($this->table.' as tbl1')
            ->selectRaw('distinct tbl2.*')
            ->join($this->table.' as tbl2', function ($join) {
                $join->on('tbl1.id', '<', 'tbl2.id')
                    ->whereColumn('tbl1.curation_id', 'tbl2.curation_id')
                    ->whereColumn('tbl1.curation_status_id', 'tbl2.curation_status_id')
                    ->whereColumn('tbl1.status_date', 'tbl2.status_date');
            })
            ->orderBy('tbl2.id');

        $fh = fopen($this->backupPath, 'w+');
        $query->chunk(5000, function ($chunk, $idx) use ($fh) {
            $chunk->each(function ($record, $i) use ($fh, $idx) {
                $record = (array) $record;
                if ($idx == 1 && $i == 0) {
                    fputcsv($fh, array_keys($record), ',', "'");
                }
                fputcsv($fh, array_values($record));
            });
        });
        fclose($fh);
    }

    private function restoreDeletedDuplicates()
    {
        $fh = fopen($this->backupPath, 'r');
        $keys = [];
        $records = [];
        while (($data = fgetcsv($fh)) !== false) {
            if (count($keys) == 0) {
                $keys = $data;
                continue;
            }
            $records[] = array_combine($keys, $data);

            if (count($records) == 100) {
                DB::table($this->table)
                    ->insert($records);
                $records = [];
            }
        }

        DB::table($this->table)
            ->insert($records);
    }
}
