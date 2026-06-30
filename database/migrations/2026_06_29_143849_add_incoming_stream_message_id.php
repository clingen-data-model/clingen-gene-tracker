<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->unsignedBigInteger('incoming_stream_message_id')->nullable()->after('id');
            $table->foreign('incoming_stream_message_id')->references('id')->on('incoming_stream_messages')->nullOnDelete();
            $table->index('incoming_stream_message_id');
        });
    }
    
    public function down(): void
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropForeign(['incoming_stream_message_id']);
            $table->dropColumn('incoming_stream_message_id');
            $table->dropIndex(['incoming_stream_message_id']);
        });
    }
};
