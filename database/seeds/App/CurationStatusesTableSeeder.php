<?php

use App\CurationStatus;
use Illuminate\Database\Seeder;

class CurationStatusesTableSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            [
                'id' => 1,
                'name' => 'Uploaded',
                'description' => 'The gene has been uploaded only, no other indication of using the database or precuration.'
            ],
            [
                'id' => 2,
                'name' => 'Precuration',
                'description' => 'The gene is in the process of precuration (this means information has been entered in the gene tracking but no assignment of MONDO ID.'
            ],
            [
                'id' => 3,
                'name' => 'Disease entity assigned',
                'description' => 'Precuration has been completed and a disease entity and MONDO is assigned (either the ID or the free text).'
            ],
            [
                'id' => 4,
                'name' => 'Curation In Progress',
                'description' => 'Curation is taking place on the GCI (currently by human, from data ex in future)'
            ],
            [
                'id' => 5,
                'name' => 'Curation Provisional',
                'description' => 'Curation is completed awaiting approval from expert'
            ],
            [
                'id' => 6,
                'name' => 'Curation Approved',
                'description' => 'Curation Approved- curation is completed and expert approved'
            ],
            [
                'id' => 7,
                'name' => 'Recuration assigned',
                'description' => 'The gene is going being recurated (either update to literature or assertion)'
            ],
            [
                'id' => 8,
                'name' => 'Retired Assignment',
                'description' => ''
            ],
            [
                'id' => 9,
                'name' => 'Published',
                'description' => ''
            ],
            [
                'id' => 10,
                'name' => 'Unublished on GCI',
                'description' => 'Denotes the curation was unpublished on the ClinGen Gene Curation Interface'
            ]
        ];

        foreach ($statuses as $status) {
            CurationStatus::unguard();
            CurationStatus::updateOrCreate(['id' => $status['id']], $status);
            CurationStatus::reguard();
        }
    }
}
