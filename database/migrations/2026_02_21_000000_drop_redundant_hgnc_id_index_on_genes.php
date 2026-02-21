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
        Schema::table('genes', function (Blueprint $table) {
            $table->dropIndex('genes_hgnc_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genes', function (Blueprint $table) {
            $table->index('hgnc_id', 'genes_hgnc_id_index');
        });
    }
};
