<?php

use App\Classification;
use Illuminate\Database\Seeder;

class ClassificationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        seedFromConfig('project.classifications', Classification::class);
    }
}
