<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRationalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rationales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table("curations", function (Blueprint $table) {
            $table->integer('rationale_id')->unsigned()->nullable()->after('pmids');
            $table->foreign('rationale_id')->references('id')->on('rationales');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("curations", function (Blueprint $table) {
            $table->dropForeign(['rationale_id']);
            $table->dropColumn('rationale_id');
        });

        Schema::dropIfExists('rationales');
    }
}
