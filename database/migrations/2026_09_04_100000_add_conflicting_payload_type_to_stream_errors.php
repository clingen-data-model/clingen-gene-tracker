<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * StoreMessageHandler needs somewhere to record a Kafka key that came back with
 * different content, and the type enum only admitted 'unmatchable curation'.
 *
 * Raw ALTER rather than ->change(): the column is an enum, and rewriting the
 * whole definition is the only way to add a member.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE stream_errors MODIFY COLUMN type"
            ." ENUM('unmatchable curation', 'conflicting message payload') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::table('stream_errors')->where('type', 'conflicting message payload')->delete();

        DB::statement(
            "ALTER TABLE stream_errors MODIFY COLUMN type"
            ." ENUM('unmatchable curation') NOT NULL"
        );
    }
};
