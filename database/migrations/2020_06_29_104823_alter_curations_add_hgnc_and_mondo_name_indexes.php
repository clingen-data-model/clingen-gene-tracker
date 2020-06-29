<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCurationsAddHgncAndMondoNameIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->index('mondo_name');
            $table->index('mondo_id');
            $table->index('hgnc_name');
            $table->index('hgnc_id');
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
            $table->dropIndex(['mondo_id']);
            $table->dropIndex(['mondo_name']);
            $table->dropIndex(['hgnc_id']);
            $table->dropIndex(['hgnc_name']);
        });
    }
}
