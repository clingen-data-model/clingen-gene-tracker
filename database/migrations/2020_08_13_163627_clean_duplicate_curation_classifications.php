<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CleanDuplicateCurationClassifications extends Migration
{
    protected $backupPath;
    protected $table = 'classification_curation';

    public function __construct()
    {
        $this->backupPath = storage_path('database/migrated_data/duplicate_curation_classifications.csv');
    }



    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->backupDuplicateForRoleback();
        $this->deleteDuplicates();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->restoreDeletedDuplicates();
    }

    private function deleteDuplicates()
    {
        DB::delete('DELETE tbl2.* FROM '.$this->table.' tbl1
                        JOIN '.$this->table.' tbl2 ON tbl1.id < tbl2.id
                    WHERE tbl1.curation_id = tbl2.curation_id
                        AND tbl1.`classification_id` = tbl2.`classification_id`
                        AND tbl1.classification_date = tbl2.`classification_date`'
                    );
    }

    private function backupDuplicateForRoleback()
    {
        $query = DB::table($this->table.' as tbl1')
            ->selectRaw('distinct tbl2.*')
            ->join($this->table.' as tbl2', function ($join) {
                $join->on('tbl1.id', '<', 'tbl2.id')
                    ->whereColumn('tbl1.curation_id', 'tbl2.curation_id')
                    ->whereColumn('tbl1.classification_id', 'tbl2.classification_id')
                    ->whereColumn('tbl1.classification_date', 'tbl2.classification_date');
                })
            ->orderBy('tbl2.id');
        
        $fh = fopen($this->backupPath, 'w+');
        $query->chunk(5000, function ($chunk, $idx) use ($fh) {
            $chunk->each(function ($record, $i) use ($fh, $idx) {
                $record = (array)$record;
                if ($idx == 1 && $i == 0) {
                    fputcsv($fh, array_keys($record),",","'");
                }
                fputcsv($fh, array_values($record));
            });
        });
        fclose($fh);
    }

    public function restoreDeletedDuplicates()
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
