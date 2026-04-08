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
            $table->foreignId('curation_id')->constrained('curations');
            $table->foreignId('archived_curation_id')->constrained('curations');
            $table->timestamps();

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
