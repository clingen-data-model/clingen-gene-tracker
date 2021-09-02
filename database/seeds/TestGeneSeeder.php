<?php

namespace Database\Seeders;

use App\Gene;
use Illuminate\Database\Seeder;

class TestGeneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $genes = [
            'BRCA1' => 1,
            'BRCA2' => 2,
            'MYL2' => 3,
            'TP53' => 4,

        ];

        foreach ($genes as $symbol => $hgnc_id) {
            Gene::create([
                'hgnc_id' => $hgnc_id,
                'gene_symbol' => $symbol,
                'hgnc_name' => uniqid(),
                'hgnc_status' => 'Approved'
            ]); 
        }
    }
}
