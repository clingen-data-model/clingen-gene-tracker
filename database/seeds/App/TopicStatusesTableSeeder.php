<?php

use App\TopicStatus;
use Illuminate\Database\Seeder;

class TopicStatusesTableSeeder extends Seeder
{
    public function run()
    {
        TopicStatus::create(['name' => 'Assigned']);
        TopicStatus::create(['name' => 'Pre-curation']);
        TopicStatus::create(['name' => 'In Curation']);
        TopicStatus::create(['name' => 'Classified']);
    }
}
