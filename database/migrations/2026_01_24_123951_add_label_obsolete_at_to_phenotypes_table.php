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
            $table->timestamp('label_obsolete_at')->nullable()->after('omim_entry');
            $table->index(['mim_number', 'name']);
            $table->index('label_obsolete_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phenotypes', function (Blueprint $table) {
            $table->dropIndex(['mim_number', 'name']);
            $table->dropIndex(['label_obsolete_at']);
            $table->dropColumn('label_obsolete_at');
        });
    }
};
