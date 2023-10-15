<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->index('mondo_name');
            $table->index('mondo_id');
            $table->index('hgnc_name');
            $table->index('hgnc_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropIndex(['mondo_id']);
            $table->dropIndex(['mondo_name']);
            $table->dropIndex(['hgnc_id']);
            $table->dropIndex(['hgnc_name']);
        });
    }
};
