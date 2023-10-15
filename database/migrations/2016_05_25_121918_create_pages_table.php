<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // TODO: use JSON data type for 'extras' instead of string
        Schema::create('pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('template');
            $table->string('name');
            $table->string('title');
            $table->string('slug');
            $table->text('content')->nullable();
            $table->text('extras')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('pages');
    }
};
