<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiseasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mondo_id', 13)->unique();
            $table->string('name');
            $table->boolean('is_obsolete')->default(false);
            $table->string('replaced_by')->nullable();
            $table->timestamps();

            $table->index('mondo_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diseases');
    }
}
