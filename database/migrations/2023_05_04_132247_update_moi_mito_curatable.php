<?php

use App\ModeOfInheritance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMoiMitoCuratable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ModeOfInheritance::findByHPId('HP:0001427')?->update(['curatable' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ModeOfInheritance::findByHPId('HP:0001427')?->update(['curatable' => 0]);
    }
}
