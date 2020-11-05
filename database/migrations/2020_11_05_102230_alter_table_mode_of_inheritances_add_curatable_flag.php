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
         * 1 => undetermined
         * 2 => AD
         * 3 => AR
         * 4 => X-linked
         * 5 => SMD
         */
        ModeOfInheritance::find([1, 2, 10, 25, 29])
            ->each(function ($moi) {
                $moi->update(['curatable' => 1]);
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
            $table->dropColumn('curatable');
        });
    }
}
