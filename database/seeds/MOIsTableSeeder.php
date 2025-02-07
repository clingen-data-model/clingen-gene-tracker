<?php
namespace Database\Seeders;

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
        ModeOfInheritance::updateOrCreate(
            ['hp_id' => 'HP:0000005'],
            [
                'name' => 'Undetermined mode of inheritance',
                'abbreviation' => 'UD',
                'parent_id' => null,
                'hp_uri' => 'http://purl.obolibrary.org/obo/HP_0000005',
            ]
        );
        collect($mois)->flatten()->each(function ($moi) {
            $parent = ModeOfInheritance::where('hp_uri', $moi->parentUri)->first();
            $hpId = 'HP:' . preg_replace('%http://purl.obolibrary.org/obo/HP_%', '', $moi->uri);
            ModeOfInheritance::updateOrCreate(['hp_id' => $hpId], [
                'name' => $moi->name,
                'abbreviation' => $moi->abbreviation,
                'hp_uri' => $moi->uri,
                'hp_id' => $hpId,
                'parent_id' => ($parent) ? $parent->id : null,
                'curatable' => in_array($hpId, [
                    'HP:0000005',
                    'HP:0000006',
                    'HP:0000007',
                    'HP:0001417',
                    'HP:0001442',
                    'HP:0032113',
                    'HP:0001427'
                ]) ? 1 : 0
            ]);
        });
        ModeOfInheritance::updateOrCreate(
            ['hp_id' => 'HP:0000000'],
            [
                'name' => 'Other',
                'abbreviation' => 'OTHER',
                'parent_id' => null,
                'hp_uri' => 'http://purl.obolibrary.org/obo/HP_0000000',
                'curatable' => 0
            ]
        );
    }
}
