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
            $table->unsignedBigInteger('moi_id')->nullable();
            $table->foreign('moi_id')->references('id')->on('mode_of_inheritances');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropForeign(['moi_id']);
            $table->dropColumn('moi_id');
        });
    }
};
