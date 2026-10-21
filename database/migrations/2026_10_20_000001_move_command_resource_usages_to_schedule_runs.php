<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = 'exit_code, host, started_at, wall_seconds, cpu_user_seconds, cpu_system_seconds,'
            .' max_rss_kb, php_peak_bytes, children_cpu_seconds, children_max_rss_kb';

        // Gives catch-up a last-run time for each task without waiting for a first schedule:run.
        DB::statement(
            "INSERT INTO schedule_runs (name, command, scheduled, $columns)"
            ." SELECT command, command, 0, $columns FROM command_resource_usages"
        );

        Schema::drop('command_resource_usages');
    }
};
