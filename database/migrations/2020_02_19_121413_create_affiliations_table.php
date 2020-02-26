<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAffiliationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('affiliations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('short_name')->unique()->nullable();
            $table->unsignedBigInteger('affiliation_type_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('clingen_id')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('affiliation_type_id')->references('id')->on('affiliation_types');
            $table->foreign('parent_id')->references('id')->on('affiliations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('affiliations');
    }
}
