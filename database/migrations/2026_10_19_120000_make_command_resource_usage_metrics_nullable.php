<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const METRICS = [
        'wall_seconds' => 'decimal',
        'cpu_user_seconds' => 'decimal',
        'cpu_system_seconds' => 'decimal',
        'max_rss_kb' => 'unsignedBigInteger',
        'children_cpu_seconds' => 'decimal',
        'children_max_rss_kb' => 'unsignedBigInteger',
        'php_peak_bytes' => 'unsignedBigInteger',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rows are now inserted when a command starts, before usage is known.
        Schema::table('command_resource_usages', function (Blueprint $table) {
            $this->defineMetrics($table, true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('command_resource_usages')->whereNull('wall_seconds')->delete();

        Schema::table('command_resource_usages', function (Blueprint $table) {
            $this->defineMetrics($table, false);
        });
    }

    private function defineMetrics(Blueprint $table, bool $nullable): void
    {
        foreach (self::METRICS as $column => $type) {
            $definition = $type === 'decimal' ? $table->decimal($column, 10, 3) : $table->unsignedBigInteger($column);
            $definition->nullable($nullable)->change();
        }
    }
};
