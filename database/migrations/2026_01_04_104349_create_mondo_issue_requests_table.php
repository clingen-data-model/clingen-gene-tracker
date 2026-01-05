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
        Schema::create('mondo_issue_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('curation_id')->index();
            $table->string('request_type', 50)->default('new_term');
            $table->string('title');
            $table->longText('body_markdown');
            $table->json('payload_json')->nullable();
            
            $table->string('github_owner')->nullable();
            $table->string('github_repo')->nullable();
            $table->unsignedInteger('github_issue_number')->nullable();
            $table->string('github_issue_url')->nullable();
            $table->string('github_state', 20)->nullable();

            $table->string('status', 20)->default('submitted');
            $table->text('last_error')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->foreign('curation_id')->references('id')->on('curations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mondo_issue_requests');
    }
};
