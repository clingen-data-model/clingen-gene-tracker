<?php

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
        factory(\App\ExpertPanel::class)->create([
            'name' => 'Cardiacmyopaphy'
        ]);
        factory(\App\ExpertPanel::class)->create([
            'name' => 'Osteoboneopathy'
        ]);
        factory(\App\ExpertPanel::class)->create([
            'name' => 'Cardiopulmonary Sadness'
        ]);
        factory(\App\ExpertPanel::class)->create([
            'name' => 'Neuropathy'
        ]);
    }
}
