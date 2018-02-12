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
        factory(\App\ExpertPanel::class, 10)->create();
    }
}
