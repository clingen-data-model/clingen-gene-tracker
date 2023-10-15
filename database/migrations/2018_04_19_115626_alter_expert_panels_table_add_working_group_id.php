<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
    public function down(): void
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->dropForeign(['working_group_id']);
            $table->dropColumn('working_group_id');
        });
    }
};
