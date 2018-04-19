<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterExpertPanelsTableAddWorkingGroupId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->integer('working_group_id')->unsigned()->nullable()->after('created_at');
            $table->foreign('working_group_id')->references('id')->on('working_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->dropForeign(['working_group_id']);
            $table->dropColumn('working_group_id');
        });
    }
}
