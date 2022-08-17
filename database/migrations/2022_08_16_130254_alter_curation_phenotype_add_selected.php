<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCurationPhenotypeAddSelected extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('curation_phenotype', function (Blueprint $table) {
            $table->boolean('selected')->default(1);
            $table->index('selected');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('curation_phenotype', function (Blueprint $table) {
            $table->dropIndex(['selected']);
            $table->dropColumn('selected');
        });
    }
}
