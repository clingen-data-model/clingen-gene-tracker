<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('curation_id');
            $table->unsignedBigInteger('upload_category_id')->nullable();
            $table->string('name');
            $table->string('file_name');
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->unsignedInteger('uploader_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('curation_id')->references('id')->on('curations');
            $table->foreign('upload_category_id')->references('id')->on('upload_categories');
            $table->foreign('uploader_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('uploads');
    }
}
