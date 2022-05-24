<?php

namespace Tests;

use App\Gene;

/**
 *
 */
trait SeedsGenes
{
    private function seedGenes()
    {
        $lines = [
            [
                'hgnc_id' => 94,
                'gene_symbol' => 'ACAT2',
                'omim_id' => '100678',
                'hgnc_name' => 'acetyl-CoA acetyltransferase 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 1552,
                'gene_symbol' => 'CBX2',
                'omim_id' => '602770',
                'hgnc_name' => 'chromobox 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 4220,
                'gene_symbol' => 'GDF5',
                'omim_id' => '601146',
                'hgnc_name' => 'growth differentiation factor 5',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 8846,
                'gene_symbol' => 'PER2',
                'omim_id' => '603426',
                'hgnc_name' => 'period circadian regulator 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 8847,
                'gene_symbol' => 'PER3',
                'omim_id' => '603427',
                'hgnc_name' => 'period circadian regulator 3',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 11600,
                'gene_symbol' => 'TBX22',
                'omim_id' => '300307',
                'hgnc_name' => 'T-box transcription factor 22',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 14211,
                'gene_symbol' => 'BLNK',
                'omim_id' => '604515',
                'hgnc_name' => 'B cell linker',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 24248,
                'gene_symbol' => 'EEF1AKNMT',
                'omim_id' => '617987',
                'hgnc_name' => 'eEF1A lysine and N-terminal methyltransferase',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 29796,
                'gene_symbol' => 'LAMTOR2',
                'omim_id' => '610389',
                'hgnc_name' => 'late endosomal/lysosomal adaptor, MAPK and MTOR activator 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 30477,
                'gene_symbol' => 'HEPHL1',
                'omim_id' => '618455',
                'hgnc_name' => 'hephaestin like 1',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 30478,
                'gene_symbol' => 'ITGB3',
                'omim_id' => '618458',
                'hgnc_name' => 'blah',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 30479,
                'gene_symbol' => 'TARDBP',
                'omim_id' => '618459',
                'hgnc_name' => 'blah',
                'hgnc_status' => 'Approved'
            ],
            
        ];

        $genes = collect();
        foreach ($lines as $geneData) {
            $genes->push(Gene::create($geneData));
        }

        return $genes;
    }
}
