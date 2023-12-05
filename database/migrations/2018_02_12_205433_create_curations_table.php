<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('curations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('gene_symbol');
            $table->integer('expert_panel_id')->unsigned()->nullable();
            $table->integer('curator_id')->unsigned()->nullable();
            $table->string('mondo_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('rationale_notes')->nullable();
            $table->json('pmids')->nullable();
            $table->text('rationale_other')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('expert_panel_id')->references('id')->on('expert_panels');
            $table->foreign('curator_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curations');
    }
};
