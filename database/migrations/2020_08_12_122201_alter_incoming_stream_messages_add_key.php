<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterIncomingStreamMessagesAddKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // $this->backupDuplicateMessagesForRoleback();
        $this->deleteDuplicateIncomingMessages();

        if (!Schema::hasColumn('incoming_stream_messages', 'key')) {
            Schema::table('incoming_stream_messages', function (Blueprint $table) {
                $table->string('key')->nullable()->after('topic')->unique();
                $table->bigInteger('timestamp')->nullable()->after('offset');
                $table->index('key');
                $table->index('timestamp');
            });
        }

        DB::update('UPDATE `incoming_stream_messages` SET `key` = CONCAT(`gdm_uuid`, "-", payload->>"$.date")');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('incoming_stream_messages', 'key')) {
            Schema::table('incoming_stream_messages', function (Blueprint $table) {
                $table->dropColumn('key');
                $table->dropColumn('timestamp');
            });
        }

        $this->restoreDeletedDuplicates();
    }

    private function deleteDuplicateIncomingMessages()
    {
        DB::delete('delete ism1.* FROM incoming_stream_messages ism1 
                        INNER JOIN incoming_stream_messages ism2
                    WHERE
                        ism1.id > ism2.id
                        AND ism1.gdm_uuid = ism2.gdm_uuid
                        AND ism1.payload->>\'$.date\' = ism2.payload->>\'$.date\'
                        AND ism1.gdm_uuid IS NOT NULL'
                    );
    }

    private function backupDuplicateMessagesForRoleback()
    {
        $query = DB::table('incoming_stream_messages as ism1')
            ->selectRaw('distinct ism2.*')
            ->join('incoming_stream_messages as ism2', function ($join) {
                $join->on('ism1.id', '<', 'ism2.id')
                    ->whereColumn('ism1.gdm_uuid', 'ism2.gdm_uuid')
                    ->whereColumn('ism1.payload', 'ism2.payload');
            })
            ->orderBy('ism2.id');

        $fh = fopen(storage_path('database/migrated_data/duplicate_incoming_stream_messages.csv'), 'w+');
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

    public function restoreDeletedDuplicates()
    {
        // dd(__METHOD__);
        $fh = fopen(storage_path('database/migrated_data/duplicate_incoming_stream_messages.csv'), 'r');
        $keys = [];
        $records = [];
        while (($data = fgetcsv($fh)) !== false) {
            // dump($data);
            if (count($keys) == 0) {
                $keys = $data;
                continue;
            }
            // dump($keys);
            $records[] = array_combine($keys, $data);

            if (count($records) == 100) {
                DB::table('incoming_stream_messages')
                    ->insert($records);
                $records = [];
            }
        }

        DB::table('incoming_stream_messages')
            ->insert($records);
    }
}
