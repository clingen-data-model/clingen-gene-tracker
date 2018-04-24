<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('curation_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->integer('curation_type_id')->unsigned()->nullable()->after('gene_symbol');
            $table->foreign('curation_type_id')->references('id')->on('curation_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('curation_types');

        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['curation_type_id']);
            $table->dropColumn('curation_type_id');
        });
    }
}
