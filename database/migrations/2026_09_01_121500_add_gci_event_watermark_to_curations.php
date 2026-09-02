<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * High-water mark for the scalar fields a GCI message overwrites in place.
 *
 * Status, classification and ownership keep dated history, so an old message
 * cannot displace them. affiliation_id, moi_id and mondo_id have no history, so
 * replaying an old message would silently overwrite current values with stale
 * ones. Recording the newest message date the curation has seen is what lets
 * those writes be skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dateTime('gci_event_watermark')->nullable()->after('gdm_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropColumn('gci_event_watermark');
        });
    }
};
