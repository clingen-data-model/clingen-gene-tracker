<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorkingGroupsAddAffiliationId extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('working_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliation_id')
                ->nullable()
                ->after('name');

            $table->foreign('affiliation_id')
                ->references('clingen_id')
                ->on('affiliations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('working_groups', function (Blueprint $table) {
            $table->dropForeign(['affiliation_id']);
            $table->dropColumn('affilliation_id');
        });
    }
}
