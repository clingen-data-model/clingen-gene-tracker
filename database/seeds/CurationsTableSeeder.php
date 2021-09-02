<?php

namespace Database\Seeders;

use App\ExpertPanel;
use App\Curation;
use App\User;
use Illuminate\Database\Seeder;

class CurationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $panels = ExpertPanel::all();
        // Curation::create([
        //     'gene_symbol' => 'MLTN15',
        //     'expert_panel_id' => $panels->random()->id,
        // ]);

        // Curation::create([
        //     'gene_symbol' => 'MYL2',
        //     'expert_panel_id' => $panels->random()->id,
        //     'curator_id' => User::all()->random()->id
        // ]);
    }
}
