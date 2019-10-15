<?php

namespace App\Contracts;

use App\HgncRecord;

interface HgncClient
{
    public function fetch($key, $value):HgncRecord;
    public function fetchGeneSymbol(string $geneSymbol):HgncRecord;
    public function fetchPreviousSymbol(string $geneSymbol):HgncRecord;
    public function fetchHgncId(string $hgncId):HgncRecord;
}
