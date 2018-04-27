<?php

use App\Rationale;
use Illuminate\Database\Seeder;

class RationalesTableSeeder extends Seeder
{
    public function run()
    {
        Rationale::unguard();
        foreach (config('project.rationales') as $id => $rationale) {
            Rationale::create([
                'id' => $id,
                'name' => $rationale
            ]);
        }
    }
}
