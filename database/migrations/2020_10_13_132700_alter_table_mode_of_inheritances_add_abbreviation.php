<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableModeOfInheritancesAddAbbreviation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mode_of_inheritances', function (Blueprint $table) {
            $table->string('abbreviation')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mode_of_inheritances', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });
    }
}
