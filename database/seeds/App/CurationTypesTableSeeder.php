<?php

use App\CurationType;
use Illuminate\Database\Seeder;

class CurationTypesTableSeeder extends Seeder
{
    public function run()
    {
        foreach (config('project.curation-types') as $name => $description) {
            CurationType::updateOrCreate([
                'name' => $name,
                'description' => $description
            ]);
        }
    }
}
