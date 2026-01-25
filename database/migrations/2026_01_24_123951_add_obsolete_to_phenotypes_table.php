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
            $table->boolean('obsolete')->default(false)->after('omim_entry');
            $table->index(['mim_number', 'name']);
            $table->index('obsolete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phenotypes', function (Blueprint $table) {
            $table->dropIndex(['mim_number', 'name']);
            $table->dropIndex(['obsolete']);
            $table->dropColumn('obsolete');
        });
    }
};
