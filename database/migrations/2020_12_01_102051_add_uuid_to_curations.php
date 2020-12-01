<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidToCurations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('curations', 'uuid')) {
            Schema::table('curations', function (Blueprint $table) {
                $table->uuid('uuid')->after('id');
                $table->unique('uuid');
            });
        }

        DB::statement('UPDATE curations SET uuid = gdm_uuid WHERE gdm_uuid IS NOT NULL');
        DB::statement('UPDATE curations SET uuid = uuid() WHERE gdm_uuid is NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('curations', 'uuid')) {
            Schema::table('curations', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
}
