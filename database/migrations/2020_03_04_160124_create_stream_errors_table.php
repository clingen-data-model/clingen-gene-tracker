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
        Schema::create('stream_errors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type', ['unmatchable curation']);
            $table->json('message_payload')->nullable();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->dateTime('notification_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_errors');
    }
};
