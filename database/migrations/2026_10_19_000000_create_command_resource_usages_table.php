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
        Schema::create('command_resource_usages', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->text('input')->nullable();
            $table->integer('exit_code')->nullable();
            $table->string('host')->nullable();
            $table->dateTime('started_at');
            $table->decimal('wall_seconds', 10, 3);
            $table->decimal('cpu_user_seconds', 10, 3);
            $table->decimal('cpu_system_seconds', 10, 3);
            $table->unsignedBigInteger('max_rss_kb');
            $table->decimal('children_cpu_seconds', 10, 3);
            $table->unsignedBigInteger('children_max_rss_kb');
            $table->unsignedBigInteger('php_peak_bytes');

            $table->index(['command', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_resource_usages');
    }
};
