<?php

use App\Curation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $curations = Curation::all();
        $curations->each(function ($curation) {
            $curation->expertPanels()->attach([
                $curation->expert_panel_id => ['start_date' => $curation->created_at],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('curation_expert_panel')->truncate();
    }
};
