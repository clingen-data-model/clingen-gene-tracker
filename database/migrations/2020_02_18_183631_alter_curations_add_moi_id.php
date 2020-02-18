<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCurationsAddMoiId extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->unsignedBigInteger('moi_id')->nullable();
            $table->foreign('moi_id')->references('id')->on('mode_of_inheritances');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropForeign(['moi_id']);
            $table->dropColumn('moi_id');
        });
    }
}
