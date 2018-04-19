<?php

use App\WorkingGroup;
use Illuminate\Database\Seeder;

class WorkingGroupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i=0; $i < 2; $i++) {
            WorkingGroup::create([
                'name' => 'Working Group '.$i
            ]);
        }
    }
}
