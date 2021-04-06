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
        if (Schema::hasTable('genes')) {
            return;
        };
        
        Schema::create('genes', function (Blueprint $table) {
            $table->unsignedBigInteger('hgnc_id')->primary();
            $table->string('gene_symbol');
            $table->string('omim_id')->nullable();
            $table->string('ncbi_gene_id')->nullable();
            $table->string('hgnc_name');
            $table->string('hgnc_status');
            $table->json('previous_symbols')->nullable();
            $table->json('alias_symbols')->nullable();
            $table->date('date_approved')->nullable();
            $table->date('date_modified')->nullable();
            $table->date('date_symbol_changed')->nullable();
            $table->date('date_name_changed')->nullable();
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
