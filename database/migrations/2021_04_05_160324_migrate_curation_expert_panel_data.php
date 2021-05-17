<?php

use App\Curation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateCurationExpertPanelData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $curations = Curation::all();
        $curations->each(function ($curation) {
            $curation->expertPanels()->attach([
                $curation->expert_panel_id => ['start_date' => $curation->created_at]
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('curation_expert_panel')->truncate();
    }
}
