<?php

namespace Database\Seeders;

use App\Affiliation;
use Illuminate\Database\Seeder;

class AffiliationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $affiliationData = json_decode(file_get_contents(__DIR__.'/../../files/new_affiliations.json'), true);

        foreach ($affiliationData as $line) {
            $this->addAffiliations($line);
        }
    }

    private function addAffiliations($data)
    {
        if ($data['submitter id'] == 'N/A') {
            return;
        }

        $parent = Affiliation::firstOrCreate(
            ['clingen_id' => $data['affiliationid']],
            [
                'name' => $data['affiliation full name'],
                'short_name' => null,
                'affiliation_type_id' => config('affiliations.types.working-group'),
            ]
        );

        if (!empty($data['gcep affiliation id'])) {
            Affiliation::updateOrCreate(
                ['clingen_id' => $data['gcep affiliation id']],
                [
                    'name' => $data['gcep affiliation name'],
                    'short_name' => null,
                    'affiliation_type_id' => config('affiliations.types.gcep'),
                    'parent_id' => $parent->id,
                ]
            );
        }

        if (!empty($data['vcep affiliation id'])) {
            Affiliation::firstOrCreate(
                ['clingen_id' => $data['vcep affiliation id']],
                [
                    'name' => $data['vcep affiliation name'],
                    'short_name' => null,
                    'affiliation_type_id' => config('affiliations.types.vcep'),
                    'parent_id' => $parent->id,
                ]
            );
        }
    }
}
