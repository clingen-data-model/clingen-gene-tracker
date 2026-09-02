<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curation history records when something happened, and "when" has a time.
 *
 * Ownership was stored as a bare date, and status dates were truncated to
 * midnight on the way in, so a curation that moved twice in one day left two rows
 * the data could not order. Everything downstream then had to guess: the current
 * status was decided by a tiebreak on the status id, and the state machine audit
 * cannot tell a legitimate same-day sequence from a broken one.
 *
 * GCI messages carry real timestamps and always did; the truncation was throwing
 * them away. Manually entered dates genuinely have no time and stay at midnight,
 * which is honest rather than invented.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curation_expert_panel', function (Blueprint $table) {
            $table->dateTime('start_date')->change();
            $table->dateTime('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('curation_expert_panel', function (Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('end_date')->nullable()->change();
        });
    }
};
