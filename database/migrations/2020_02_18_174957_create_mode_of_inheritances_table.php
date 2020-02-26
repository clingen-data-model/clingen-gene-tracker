<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModeOfInheritancesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('mode_of_inheritances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('hp_id')->unique();
            $table->string('hp_uri')->unique();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('mode_of_inheritances');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('mode_of_inheritances');
    }
}
