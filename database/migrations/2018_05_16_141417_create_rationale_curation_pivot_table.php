<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRationaleCurationPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('curation_rationale', function (Blueprint $table) {
            $table->integer('rationale_id')->unsigned()->index();
            $table->foreign('rationale_id')->references('id')->on('rationales')->onDelete('cascade');
            $table->integer('curation_id')->unsigned()->index();
            $table->foreign('curation_id')->references('id')->on('curations')->onDelete('cascade');
            $table->primary(['rationale_id', 'curation_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('rationale_curation');
    }
}
