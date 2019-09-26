<?php

namespace App\Contracts;

interface HgncClient
{
    public function fetchGeneSymbol(string $geneSymbol):object;
    public function fetchPreviousSymbol(string $geneSymbol):object;
}