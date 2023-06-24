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
    public function up()
    {
        Schema::create('classification_curation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('classification_id')->index();
            $table->foreign('classification_id', 'classification_id_foreign')->references('id')->on('classifications')->onDelete('cascade');

            $table->unsignedInteger('curation_id')->index();
            $table->foreign('curation_id', 'curation_id_foreign')->references('id')->on('curations')->onDelete('cascade');

            $table->datetime('classification_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('classification_curation');
    }
};
