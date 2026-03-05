<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MondoClingenLabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mondo_clingen_label')->insert([
            [
                'mondo_id' => 'MONDO:0018997',
                'xref_genevalidation' => 'CGGV:assertion_d910a9d8-516e-443d-acba-8d61f7574792-2018-06-07T160000.000Z',
                'xref_source' => 'MONDO:CLINGEN',
                'see_also' => 'https://search.clinicalgenome.org/kb/conditions/MONDO:0018997',
                'see_also_source' => 'MONDO:CLINGEN',
                'clingen_label' => 'Noonan syndrome',
                'clingen_label_type' => 'http://purl.obolibrary.org/obo/mondo#CLINGEN_LABEL',
                'clingen_label_source' => 'MONDO:CLINGEN|pmid:123',
                'subset' => 'http://purl.obolibrary.org/obo/mondo#clingen',
                'subset_source' => 'MONDO:CLINGEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mondo_id' => 'MONDO:0044970',
                'xref_genevalidation' => 'CGGV:assertion_583e237c-18f6-4427-a04f-82ea0f020daf-2022-04-18T160000.000Z',
                'xref_source' => 'MONDO:CLINGEN',
                'see_also' => 'https://search.clinicalgenome.org/kb/conditions/MONDO:0044970',
                'see_also_source' => 'MONDO:CLINGEN',
                'clingen_label' => 'mitochondrial disease',
                'clingen_label_type' => 'http://purl.obolibrary.org/obo/mondo#CLINGEN_LABEL',
                'clingen_label_source' => 'MONDO:CLINGEN',
                'subset' => 'http://purl.obolibrary.org/obo/mondo#clingen',
                'subset_source' => 'MONDO:CLINGEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mondo_id' => 'MONDO:0001197',
                'xref_genevalidation' => 'CGGV:assertion_2828abac-5b4a-4dad-a703-10c0daf35dbd-2022-05-25T160000.000Z',
                'xref_source' => 'MONDO:CLINGEN',
                'see_also' => 'https://search.clinicalgenome.org/kb/conditions/MONDO:0001197',
                'see_also_source' => 'MONDO:CLINGEN',
                'clingen_label' => 'qualitative platelet defect',
                'clingen_label_type' => 'http://purl.obolibrary.org/obo/mondo#CLINGEN_LABEL',
                'clingen_label_source' => 'MONDO:CLINGEN',
                'subset' => 'http://purl.obolibrary.org/obo/mondo#clingen',
                'subset_source' => 'MONDO:CLINGEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mondo_id' => 'MONDO:0015924',
                'xref_genevalidation' => 'CGGV:assertion_14b1739d-8890-4927-8efb-e909f48bad5a-2022-10-10T160000.000Z',
                'xref_source' => 'MONDO:CLINGEN',
                'see_also' => 'https://search.clinicalgenome.org/kb/conditions/MONDO:0015924',
                'see_also_source' => 'MONDO:CLINGEN',
                'clingen_label' => 'pulmonary arterial hypertension',
                'clingen_label_type' => 'http://purl.obolibrary.org/obo/mondo#CLINGEN_LABEL',
                'clingen_label_source' => 'MONDO:CLINGEN',
                'subset' => 'http://purl.obolibrary.org/obo/mondo#clingen',
                'subset_source' => 'MONDO:CLINGEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mondo_id' => 'MONDO:0008863',
                'xref_genevalidation' => 'CGGV:assertion_c52c7403-8975-4a3f-8796-a966e977f708-2020-07-10T160000.000Z',
                'xref_source' => 'MONDO:CLINGEN',
                'see_also' => 'https://search.clinicalgenome.org/kb/conditions/MONDO:0008863',
                'see_also_source' => 'MONDO:CLINGEN',
                'clingen_label' => 'sitosterolemia',
                'clingen_label_type' => 'http://purl.obolibrary.org/obo/mondo#CLINGEN_LABEL',
                'clingen_label_source' => 'MONDO:CLINGEN',
                'subset' => 'http://purl.obolibrary.org/obo/mondo#clingen',
                'subset_source' => 'MONDO:CLINGEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mondo_id' => 'MONDO:0020531',
                'xref_genevalidation' => 'CGGV:assertion_d95637bf-c628-4d23-a0d5-656db55a09a4-2021-01-25T194827.344Z',
                'xref_source' => 'MONDO:CLINGEN',
                'see_also' => 'https://search.clinicalgenome.org/kb/conditions/MONDO:0020531',
                'see_also_source' => 'MONDO:CLINGEN',
                'clingen_label' => 'long chain acyl-CoA dehydrogenase deficiency',
                'clingen_label_type' => 'http://purl.obolibrary.org/obo/mondo#CLINGEN_LABEL',
                'clingen_label_source' => 'MONDO:CLINGEN',
                'subset' => 'http://purl.obolibrary.org/obo/mondo#clingen',
                'subset_source' => 'MONDO:CLINGEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
