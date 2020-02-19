<?php

use App\ModeOfInheritance;
use Illuminate\Database\Seeder;

class MOIsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $mois = json_decode(file_get_contents(base_path('files/moi.json')));
        collect($mois)->flatten()->each(function ($moi) {
            $parent = ModeOfInheritance::where('hp_uri', $moi->parentUri)->first();
            $hpId = preg_replace('%http://purl.obolibrary.org/obo/HP_%', '', $moi->uri);
            ModeOfInheritance::firstOrCreate(['name' => $moi->name], [
                'name' => $moi->name,
                'hp_uri' => $moi->uri,
                'hp_id' => 'HP:'.$hpId,
                'parent_id' => ($parent) ? $parent->id : null,
            ]);
        });
    }
}
