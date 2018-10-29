<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCurationCurationStatusesTableAddStatusDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('curation_curation_status', function (Blueprint $table) {
            $table->timestamp('status_date')->default(\DB::raw('CURRENT_TIMESTAMP'))->after('curation_id');
        });
        \DB::table('curation_curation_status')->get()->each(function ($csRow) {
            \DB::table('curation_curation_status')
                ->where('id', $csRow->id)
                ->update(['status_date' => $csRow->created_at]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('curation_curation_status', function (Blueprint $table) {
            $table->dropColumn('status_date');
        });
    }
}
