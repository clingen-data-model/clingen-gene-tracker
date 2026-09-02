<?php

use App\Curation;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCurationsAddCurationStatusIdAndSetLatestStatusId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('curations', 'curation_status_id')) {
            Schema::table('curations', function (Blueprint $table) {
                $table->unsignedInteger('curation_status_id')->default(1)->after('hgnc_id');
            });
        }
        // Was Artisan::call('curations:set_current_status_id'). That command has been
        // replaced by curations:rebuild-projections; the SQL is inlined so this
        // migration does not depend on a command that may change or go away.
        DB::statement(
            'UPDATE curations c
                JOIN (
                    SELECT ccs.curation_id, ccs.curation_status_id
                    FROM curation_curation_status ccs
                    JOIN (
                        SELECT curation_id, MAX(status_date) AS status_date
                        FROM curation_curation_status GROUP BY curation_id
                    ) newest
                        ON newest.curation_id = ccs.curation_id
                        AND newest.status_date = ccs.status_date
                    GROUP BY ccs.curation_id, ccs.curation_status_id
                ) latest ON latest.curation_id = c.id
            SET c.curation_status_id = latest.curation_status_id'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('curations', 'curation_status_id')) {
            Schema::table('curations', function (Blueprint $table) {
                $table->dropColumn('curation_status_id');
            });
        }
    }
}
