<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->increments('id');
            $table->string('gene_symbol');
            $table->integer('expert_panel_id')->unsigned()->nullable();
            $table->integer('curator_id')->unsigned()->nullable();
            $table->string('mondo_id')->nullable();
            $table->date('curation_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('rationale_notes')->nullable();
            $table->json('pmids')->nullable();
            $table->text('rationale_other')->nullable();
            $table->timestamps();

            $table->foreign('expert_panel_id')->references('id')->on('expert_panels');
            $table->foreign('curator_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('topics');
    }
}
