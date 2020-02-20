<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterExpertPanelsAddAffiliationId extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('expert_panels', function (Blueprint $table) {
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
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->dropForeign(['affiliation_id']);
            $table->dropColumn('affilliation_id');
        });
    }
}
