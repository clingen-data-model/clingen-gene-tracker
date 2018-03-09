<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhenotypeTopicPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('phenotype_topic', function (Blueprint $table) {
            $table->integer('phenotype_id')->unsigned()->index();
            $table->foreign('phenotype_id')->references('id')->on('phenotypes')->onDelete('cascade');
            $table->integer('topic_id')->unsigned()->index();
            $table->foreign('topic_id')->references('id')->on('topics')->onDelete('cascade');
            $table->primary(['phenotype_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('phenotype_topic');
    }
}
