<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('genes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('gene_symbol');
            $table->integer('hgnc_id');
            $table->integer('omim_id')->nullable();
            $table->integer('ncbi_gene_id')->nullable();
            $table->string('hgnc_name');
            $table->string('hgnc_status');
            $table->json('previous_symbols')->nullable();
            $table->json('alias_symbols')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('gene_symbol');
            $table->index('hgnc_id');
            $table->index('omim_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('genes');
    }
}
