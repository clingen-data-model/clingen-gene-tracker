<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('curation_curation_status', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('curation_status_id')->unsigned()->index();
            $table->foreign('curation_status_id')->references('id')->on('curation_statuses')->onDelete('cascade');
            $table->integer('curation_id')->unsigned()->index();
            $table->foreign('curation_id')->references('id')->on('curations')->onDelete('cascade');
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
        Schema::drop('curation_status_curation');
    }
};
