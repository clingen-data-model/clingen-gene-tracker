<?php

use App\Curation;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
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
        Artisan::call('curations:set_current_status_id');
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
