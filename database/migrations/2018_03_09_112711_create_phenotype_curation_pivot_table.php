<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhenotypeCurationPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('curation_phenotype', function (Blueprint $table) {
            $table->integer('phenotype_id')->unsigned()->index();
            $table->foreign('phenotype_id')->references('id')->on('phenotypes')->onDelete('cascade');
            $table->integer('curation_id')->unsigned()->index();
            $table->foreign('curation_id')->references('id')->on('curations')->onDelete('cascade');
            $table->primary(['phenotype_id', 'curation_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('phenotype_curation');
    }
}
