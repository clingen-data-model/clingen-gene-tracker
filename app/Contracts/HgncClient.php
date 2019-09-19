<?php

namespace App\Contracts;

interface HgncClient
{
    public function fetchGeneSymbol(string $geneSymbol):object;
}