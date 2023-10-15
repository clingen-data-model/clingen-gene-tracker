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
        if (Schema::hasTable('app_states')) {
            return;
        }
        Schema::create('app_states', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->text('value')->nullable();
            $table->text('default')->nullable();
            $table->enum('type', [
                'string',
                'int',
                'integer',
                'float',
                'bool',
                'boolean',
                'date',
                'json',
            ])->default('string');
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
        Schema::dropIfExists('app_states');
    }
};
