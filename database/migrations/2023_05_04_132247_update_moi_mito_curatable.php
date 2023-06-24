<?php

use App\ModeOfInheritance;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
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
};
