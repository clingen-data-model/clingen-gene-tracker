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
        Schema::table('affiliations', function (Blueprint $table) {
            // Drop existing unique indexes by name
            $table->dropUnique('affiliations_name_unique');
            $table->dropUnique('affiliations_short_name_unique');

            // Add new composite unique indexes
            $table->unique(['name', 'affiliation_type_id'], 'affiliations_name_affiliation_type_unique');
            $table->unique(['short_name', 'affiliation_type_id'], 'affiliations_short_name_affiliation_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliations', function (Blueprint $table) {
            // Drop composite indexes
            $table->dropUnique('affiliations_name_affiliation_type_unique');
            $table->dropUnique('affiliations_short_name_affiliation_type_unique');

            // Restore original unique constraints
            $table->unique('name', 'affiliations_name_unique');
            $table->unique('short_name', 'affiliations_short_name_unique');
        });
    }

};
