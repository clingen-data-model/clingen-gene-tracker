<?php

use App\ModeOfInheritance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableModeOfInheritancesAddCuratableFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mode_of_inheritances', function (Blueprint $table) {
            $table->boolean('curatable')->after('parent_id')->default(0);
        });

        /*
         * undetermined
         * AD
         * AR
         * X-linked
         * SMD
         */
        foreach (['HP:0000005', 'HP:0000006', 'HP:0000007', 'HP:0001417', 'HP:0032113']
            as $hpId) {
                ModeOfInheritance::findByHpId($hpId)?->update(['curatable' => 1]);
            }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mode_of_inheritances', function (Blueprint $table) {
            $table->dropColumn('curatable');
        });
    }
}
