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
        Schema::create('incoming_stream_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('topic');
            $table->integer('partition');
            $table->integer('offset');
            $table->integer('error_code');
            $table->json('payload')->nullable();
            $table->uuid('gdm_uuid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_stream_messages');
    }
};
