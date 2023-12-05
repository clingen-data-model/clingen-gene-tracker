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
        Schema::table('phenotypes', function (Blueprint $table) {
            $table->dropUnique(['mim_number']);
            $table->unique(['mim_number', 'name']);
            $table->index('mim_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phenotypes', function (Blueprint $table) {
            $table->dropUnique(['mim_number', 'name']);
            $table->dropIndex(['mim_number']);
            $table->unique('mim_number');
        });
    }
};
