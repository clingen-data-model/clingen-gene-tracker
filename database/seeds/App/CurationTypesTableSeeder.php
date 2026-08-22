<?php

namespace Database\Seeders\App;

use App\CurationType;
use Illuminate\Database\Seeder;

class CurationTypesTableSeeder extends Seeder
{
    public function run()
    {
        foreach (config('project.curation-types') as $name => $description) {
            CurationType::updateOrCreate(
                ['id' => config("project.curation-type-ids.{$name}")],
                [
                    'name' => $name,
                    'description' => $description,
                ]
            );
        }
    }
}
