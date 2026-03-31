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
            $table->timestamp('archived_at')->nullable()->index()->after('affiliation_id');
            $table->text('archive_reason')->nullable()->after('archived_at');
            $table->string('gcex_url')->nullable()->after('archive_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn([
                'archived_at',
                'archive_reason',
                'gcex_url',
            ]);
        });
    }
};
