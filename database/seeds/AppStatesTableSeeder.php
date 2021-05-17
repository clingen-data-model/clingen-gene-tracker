<?php

use App\AppState;
use Illuminate\Database\Seeder;

class AppStatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $states = config('app_state');
        foreach ($states as  $value) {
            AppState::firstOrCreate(['name' => $value['name']], $value);
        }
    }
}
