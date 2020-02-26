<?php

use App\Affiliation;
use Illuminate\Database\Seeder;

class AffiliationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $affiliationData = json_decode(file_get_contents(__DIR__.'/../../files/affiliations.json'));

        $keys = null;
        foreach ($affiliationData as $values) {
            if (!$keys) {
                $keys = array_map(
                    function ($key) {
                        return strtolower($key);
                    },
                    $values
                );
                continue;
            }

            $values = array_map(
                function ($value) {
                    return !empty($value) ? $value : null;
                },
                $values
            );

            $data = array_combine($keys, array_pad(array_slice($values, 0, count($keys)), count($keys), null));

            $this->addAffiliations($data);
        }
    }

    private function addAffiliations($data)
    {
        $parent = Affiliation::firstOrCreate(
            ['clingen_id' => $data['affiliationid']],
            [
                'name' => $data['affiliation full name'],
                'short_name' => $data['short base name (max 15 chars)'],
                'affiliation_type_id' => config('affiliations.types.working-group'),
            ]
        );

        if (!empty($data['gcep id'])) {
            Affiliation::updateOrCreate(
                ['clingen_id' => $data['gcep id']],
                [
                    'name' => $data['website (gcep long name)'],
                    'short_name' => $data['website (gcep short name)'],
                    'affiliation_type_id' => config('affiliations.types.gcep'),
                    'parent_id' => $parent->id,
                ]
            );
        }

        if (!empty($data['vcep id'])) {
            Affiliation::firstOrCreate(
                ['clingen_id' => $data['vcep id']],
                [
                    'name' => $data['website (vcep long name)'],
                    'short_name' => $data['website (vcep short name)'],
                    'affiliation_type_id' => config('affiliations.types.vcep'),
                    'parent_id' => $parent->id,
                ]
            );
        }
    }
}
