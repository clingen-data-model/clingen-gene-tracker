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
        if (Schema::hasTable('state_variables')) {
            Schema::drop('state_variables');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('state_variables')) {
            Schema::create('state_variables', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->enum('type', ['string', 'integer', 'float', 'boolean', 'array', 'object'])->default('integer');
                $table->text('value');
                $table->timestamps();
            });
        }
    }
};
