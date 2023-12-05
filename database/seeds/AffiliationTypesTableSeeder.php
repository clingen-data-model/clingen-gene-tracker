<?php

namespace Database\Seeders;

use App\AffiliationType;
use Illuminate\Database\Seeder;

class AffiliationTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('affiliations.types') as $slug => $id) {
            $name = preg_replace('/-/', ' ', $slug);
            AffiliationType::updateOrCreate(compact('id'), compact('name'));
        }
    }
}
