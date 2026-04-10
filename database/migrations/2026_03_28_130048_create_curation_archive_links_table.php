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
        Schema::create('curation_archive_links', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('curation_id');
            $table->unsignedInteger('archived_curation_id');
            $table->timestamps();

            $table->foreign('curation_id')->references('id')->on('curations');
            $table->foreign('archived_curation_id')->references('id')->on('curations');

            $table->unique(['curation_id', 'archived_curation_id']);
            $table->index('archived_curation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curation_archive_links');
    }
};
