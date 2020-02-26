<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAffiliationUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('affiliation_user', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliation_id');
            $table->foreign('affiliation_id')->references('id')->on('affiliations')->onDelete('cascade');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('can_edit_curations')->default(0);
            $table->boolean('is_coordinator')->default(0);
            $table->boolean('is_curator')->default(0);
            $table->timestamps();

            $table->primary(['affiliation_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('affiliation_user');
    }
}
