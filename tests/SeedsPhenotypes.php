<?php

namespace Tests;

use App\Phenotype;

trait SeedsPhenotypes
{
    public function seedPhenotypes($data = null)
    {
        if (!$data) {
            $data = [
                [
                    'name' => 'Cardiomyopathy, hypertrophic, 2',
                    'mim_number' => 115195,
                    'moi' => 'Autosomal dominant'
                ],
                [
                    'name' => 'Neurofibromatosis, type 2',
                    'mim_number' => 607084,
                    'moi' => 'Autosomal recessive'
                ],
                [
                    'name' => ' Retinoblastoma',
                    'mim_number' => 180200,
                    'moi' => 'Atosomal recessive'
                ]
            ];
        }
        
        $phenotypes = collect();
        foreach ($data as $d) {
            $ph = Phenotype::create($d);
            $phenotypes->push($ph);
        }

        return $phenotypes;
    }
}
