<?php

namespace Tests;

use App\Disease;

trait SeedsDiseases
{
    private function seedDiseases($data = null)
    {
        if (!$data) {
            $data = [
                [
                    'mondo_id' => 'MONDO:0000900',
                    'name' => 'obsolete PTEN hamartoma tumor syndrome',
                    'doid_id' => null,
                    'is_obsolete' => true,
                    'replaced_by' => 'MONDO:0017623',
                ],
                [
                    'mondo_id' => 'MONDO:0017623',
                    'name' => 'PTEN hamartoma tumor syndrome',
                    'doid_id' => 'DOID:0080191',
                    'is_obsolete' => false,
                    'replaced_by' => null,
                ],
            ];
        }

        $diseases = collect();
        foreach ($data as $d) {
            $disease = Disease::create($d);
            $diseases->push($disease);
        }

        return $diseases;
    }
}
