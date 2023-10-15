<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(WorkingGroupsTableSeeder::class);
        $this->call(ExpertPanelsTableSeeder::class);
        $this->call(UsersTableSeeder::class);
    }
}
