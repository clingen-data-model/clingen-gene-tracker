<?php

use App\CurationStatus;
use Illuminate\Database\Seeder;

class CurationStatusesTableSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            [
                'name' => 'Uploaded',
                'description' => 'The gene has been uploaded only, no other indication of using the database or precuration.'
            ],
            [
                'name' => 'Precuration',
                'description' => 'The gene is in the process of precuration (this means information has been entered in the gene tracking but no assignment of MONDO ID.'
            ],
            [
                'name' => 'Disease entity assigned',
                'description' => 'Precuration has been completed and a disease entity and MONDO is assigned (either the ID or the free text).'
            ],
            [
                'name' => 'Curation In Progress',
                'description' => 'Curation is taking place on the GCI (currently by human, from data ex in future)'
            ],
            [
                'name' => 'Curation Provisional',
                'description' => 'Curation is completed awaiting approval from expert'
            ],
            [
                'name' => 'Curation Approved',
                'description' => 'Curation Approved- curation is completed and expert approved'
            ],
            [
                'name' => 'Recuration assigned',
                'description' => 'The gene is going being recurated (either update to literature or assertion)'
            ],
            [
                'name' => 'Retired Assignment',
                'description' => ''
            ],
            [
                'name' => 'Published',
                'description' => ''
            ]
        ];

        foreach ($statuses as $status) {
            CurationStatus::updateOrCreate(['name' => $status['name']], $status);
        }
    }
}
