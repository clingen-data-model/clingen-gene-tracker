<?php

use App\TopicStatus;
use Illuminate\Database\Seeder;

class TopicStatusesTableSeeder extends Seeder
{
    public function run()
    {
        TopicStatus::create([
            'name' => 'Uploaded',
            'description' => 'The gene has been uploaded only, no other indication of using the database or precuration.'
        ]);
        TopicStatus::create([
            'name' => 'Precuration',
            'description' => 'The gene is in the process of precuration (this means information has been entered in the gene tracking but no assignment of MONDO ID.'
        ]);
        TopicStatus::create([
            'name' => 'Disease entity assigned',
            'description' => 'Precuration has been completed and a disease entity and MONDO is assigned (either the ID or the free text).'
        ]);
        TopicStatus::create([
            'name' => 'Curation In Progress',
            'description' => 'Curation is taking place on the GCI (currently by human, from data ex in future)'
        ]);
        TopicStatus::create([
            'name' => 'Curation Provisional',
            'description' => 'Curation is completed awaiting approval from expert'
        ]);
        TopicStatus::create([
            'name' => 'Curation Approved',
            'description' => 'Curation Approved- curation is completed and expert approved'
        ]);
        TopicStatus::create([
            'name' => 'Recuration assigned',
            'description' => 'The gene is going being recurated (either update to literature or assertion)'
        ]);
    }
}
