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
        $names = [
            'milton',
            'tj',
            'courtney',
            'jenny',
            'maria',
            'jonathan',
            'barry',
            'morticia',
            'gomez',
            'wednesday',
            'bob',
            'louise',
            'gene',
            'linda',
            'tina'
        ];
        foreach ($names as $name) {
            WorkingGroup::create([
                'name' => ucfirst($name)."'s Working Group"
            ]);
        }
    }
}
