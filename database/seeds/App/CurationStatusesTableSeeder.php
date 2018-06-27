<?php

use App\CurationStatus;
use Illuminate\Database\Seeder;

class CurationStatusesTableSeeder extends Seeder
{
    public function run()
    {
        CurationStatus::create([
            'name' => 'Uploaded',
            'description' => 'The gene has been uploaded only, no other indication of using the database or precuration.'
        ]);
        CurationStatus::create([
            'name' => 'Precuration',
            'description' => 'The gene is in the process of precuration (this means information has been entered in the gene tracking but no assignment of MONDO ID.'
        ]);
        CurationStatus::create([
            'name' => 'Disease entity assigned',
            'description' => 'Precuration has been completed and a disease entity and MONDO is assigned (either the ID or the free text).'
        ]);
        CurationStatus::create([
            'name' => 'Curation In Progress',
            'description' => 'Curation is taking place on the GCI (currently by human, from data ex in future)'
        ]);
        CurationStatus::create([
            'name' => 'Curation Provisional',
            'description' => 'Curation is completed awaiting approval from expert'
        ]);
        CurationStatus::create([
            'name' => 'Curation Approved',
            'description' => 'Curation Approved- curation is completed and expert approved'
        ]);
        CurationStatus::create([
            'name' => 'Recuration assigned',
            'description' => 'The gene is going being recurated (either update to literature or assertion)'
        ]);
    }
}
