<?php

namespace App\Contracts;

use App\Contracts\GeneSymbolUpdate;

interface HgncClient
{
    public function fetchGeneSymbol(string $geneSymbol):object;
    public function fetchPreviousSymbol(string $geneSymbol):object;
    public function fetchGeneSymbolUpdate(string $geneSymbol):GeneSymbolUpdate;
}