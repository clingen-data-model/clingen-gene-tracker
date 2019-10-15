<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCurationsAddHgncIdAndMondoName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->unsignedBigInteger('hgnc_id')->nullable()->after('gene_symbol');
            $table->string('hgnc_name')->nullable()->after('gene_symbol');
            $table->string('mondo_name')->nullable()->after('mondo_id');
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
            $table->dropColumn('hgnc_id');
            $table->dropColumn('hgnc_name');
            $table->dropColumn('mondo_name');
        });
    }
}
