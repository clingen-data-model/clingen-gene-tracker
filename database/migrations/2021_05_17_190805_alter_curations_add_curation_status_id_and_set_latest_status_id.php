<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('curations', 'curation_status_id')) {
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
};
