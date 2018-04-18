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
            'name' => 'Cardiacmyopaphy'
        ]);
        ExpertPanel::create([
            'name' => 'Osteoboneopathy'
        ]);
        ExpertPanel::create([
            'name' => 'Cardiopulmonary Sadness'
        ]);
        ExpertPanel::create([
            'name' => 'Neuropathy'
        ]);
    }
}
