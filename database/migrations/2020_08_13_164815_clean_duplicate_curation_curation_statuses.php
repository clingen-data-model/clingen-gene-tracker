<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Historical cleanup, kept self-contained.
 *
 * This used to call App\Services\Utilities\CleanDuplicateCurationStatuses, which
 * has since been removed: the unique index added in
 * 2026_09_01_120500_add_source_keys_to_curation_history_pivots now prevents these
 * duplicates from being created in the first place. The SQL is inlined so the
 * migration keeps working on a fresh database.
 */
class CleanDuplicateCurationCurationStatuses extends Migration
{
    public function up()
    {
        DB::delete(
            'DELETE dupe FROM curation_curation_status dupe
                JOIN curation_curation_status keeper ON keeper.id < dupe.id
            WHERE keeper.curation_id = dupe.curation_id
                AND keeper.curation_status_id = dupe.curation_status_id
                AND keeper.status_date = dupe.status_date'
        );
    }

    public function down()
    {
        // Deleted duplicates are not restorable, and are not wanted back.
    }
}
