<?php

use App\ExpertPanel;
use Illuminate\Database\Seeder;

class ExpertPanelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ExpertPanel::create([
            'name' => 'Cardiacmyopaphy',
            'working_group_id' => 1,
        ]);
        ExpertPanel::create([
            'name' => 'Osteoboneopathy',
            'working_group_id' => 1,
        ]);
        ExpertPanel::create([
            'name' => 'Cardiopulmonary Sadness',
            'working_group_id' => 2,
        ]);
        ExpertPanel::create([
            'name' => 'Neuropathy',
            'working_group_id' => 2,
        ]);
    }
}
