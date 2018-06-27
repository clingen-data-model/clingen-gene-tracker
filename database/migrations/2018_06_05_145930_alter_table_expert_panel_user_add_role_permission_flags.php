<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableExpertPanelUserAddRolePermissionFlags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expert_panel_user', function (Blueprint $table) {
            $table->boolean('can_edit_curations')->default(0);
            $table->boolean('is_curator')->default(0);
            $table->boolean('is_coordinator')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expert_panel_user', function (Blueprint $table) {
            $table->dropColumn('can_edit_curations');
            $table->dropColumn('is_curator');
            $table->dropColumn('is_coordinator');
        });
    }
}
