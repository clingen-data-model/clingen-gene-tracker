<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliation_id')->nullable();
            $table->foreign('affiliation_id')->references('id')->on('affiliations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->dropForeign(['affiliation_id']);
            $table->dropColumn('affiliation_id');
        });
    }
};
