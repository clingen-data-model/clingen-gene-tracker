<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGciCurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::create('gci_curations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('gdm_uuid');
                $table->unsignedBigInteger('hgnc_id');
                $table->string('mondo_id');
                $table->unsignedBigInteger('moi_id');
                $table->unsignedBigInteger('classification_id')->nullable();
                $table->unsignedInteger('status_id');
                $table->unsignedBigInteger('affiliation_id')->nullable();
                $table->uuid('creator_uuid');
                $table->string('creator_email');
                $table->timestamps();
                
                $table->foreign('hgnc_id')->references('hgnc_id')->on('genes');
                $table->foreign('moi_id')->references('id')->on('mode_of_inheritances');
                $table->foreign('classification_id')->references('id')->on('classifications');
                $table->foreign('status_id')->references('id')->on('curation_statuses');
                $table->foreign('affiliation_id')->references('id')->on('affiliations');
                $table->index('gdm_uuid');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gci_curations');
    }
}
