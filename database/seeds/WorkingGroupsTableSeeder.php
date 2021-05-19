<?php

use App\WorkingGroup;
use Illuminate\Database\Seeder;

class WorkingGroupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $wgs = [
                ['id'=>16, 'name' => 'Neurodevelopmental Disorders CDWG'],
                ['id'=>17, 'name' => 'Hearing Loss CDWG'],
                ['id'=>18, 'name' => 'Cardiovascular CDWG'],
                ['id'=>19, 'name' => 'Inborn Errors Metabolism CDWG'],
                ['id'=>20, 'name' => 'Hereditary Cancer CDWG'],
                ['id'=>21, 'name' => 'Hemostasis/Thrombosis CDWG'],
                ['id'=>22, 'name' => 'RASopathy CDWG'],
                ['id'=>23, 'name' => 'Gene Curation Working Group'],
                ['id'=>24, 'name' => 'Neuromuscular CDWG'],
                ['id'=>25, 'name' => 'Actionability'],
                ['id'=>26, 'name' => 'External curation groups'],
                ['id'=>27, 'name' => 'Kidney Disease CDWG'],
                ['id'=>28, 'name' => 'Skeletal Disorders CDWG'],
                ['id'=>29, 'name' => 'Ocular CDWG'],
                ['id'=>30, 'name' => 'Immunology CDWG'],
                ['id'=>31, 'name' => 'Neurodegenerative'],
        ];

        foreach ($wgs as $wg) {
            WorkingGroup::create($wg);
        }
    }
}
