<?php

use App\ExpertPanel;
use App\Topic;
use App\User;
use Illuminate\Database\Seeder;

class TopicsTableSeeder extends Seeder
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
        Topic::create([
            'gene_symbol' => 'MLTN15',
            'expert_panel_id' => $panels->random()->id,
        ]);

        Topic::create([
            'gene_symbol' => 'MYL2',
            'expert_panel_id' => $panels->random()->id,
            'curator_id' => User::role('curator')->get()->random()->id
        ]);
    }
}
