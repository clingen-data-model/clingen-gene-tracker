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
        Schema::table('curations', function (Blueprint $table) {
            $table->renameColumn('notes', 'curation_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->renameColumn('curation_notes', 'notes');
        });
    }
};
