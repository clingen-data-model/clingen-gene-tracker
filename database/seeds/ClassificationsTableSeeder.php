<?php

namespace Database\Seeders;

use App\Classification;
use Illuminate\Database\Seeder;

class ClassificationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        seedFromConfig('project.classifications', Classification::class);
    }
}
