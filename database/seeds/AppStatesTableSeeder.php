<?php

namespace Database\Seeders;

use App\AppState;
use Illuminate\Database\Seeder;

class AppStatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = config('app_state');
        foreach ($states as $value) {
            AppState::firstOrCreate(['name' => $value['name']], $value);
        }
    }
}
