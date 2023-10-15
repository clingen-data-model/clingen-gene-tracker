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
        Schema::table('mode_of_inheritances', function (Blueprint $table) {
            $table->string('abbreviation')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mode_of_inheritances', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });
    }
};
