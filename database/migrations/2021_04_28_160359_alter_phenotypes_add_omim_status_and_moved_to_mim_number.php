<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPhenotypesAddOmimStatusAndMovedToMimNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('phenotypes', function (Blueprint $table) {
            $table->enum('omim_status', ['live', 'moved', 'removed'])->default('live')->after('name');
            $table->integer('moved_to_mim_number')->nullable()->after('omim_status');

            $table->foreign('moved_to_mim_number', 'moved_to_mim_number_foreign')->references('mim_number')->on('phenotypes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('phenotypes', function (Blueprint $table) {
            $table->dropForeign('moved_to_mim_number_foreign');
            $table->dropColumn('moved_to_mim_number');
            $table->dropColumn('omim_status');
        });
    }
}
