<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCurationsAddGdmUuid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->uuid('gdm_uuid')->nullable()->after('gene_symbol');

            $table->index('gdm_uuid')->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropIndex('gdm_uuid');

            $table->dropColumn('gdm_uuid');

        });
    }
}
