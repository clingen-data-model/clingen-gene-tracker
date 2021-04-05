<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenePhenotypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gene_phenotype', function (Blueprint $table) {
            $table->unsignedBigInteger('hgnc_id');
            $table->unsignedInteger('phenotype_id');
            $table->foreign('hgnc_id', 'hgnc_id_foreign')->references('hgnc_id')->on('genes');
            $table->foreign('phenotype_id', 'phenotype_id_foreign')->references('id')->on('phenotypes');
            $table->primary(['hgnc_id', 'phenotype_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gene_phenotype');
    }
}
