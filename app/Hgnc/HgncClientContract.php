<?php

namespace App\Hgnc;

use Illuminate\Support\Collection;

interface HgncClientContract
{
    public function fetch($key, $value): HgncRecord;

    public function fetchGeneSymbol(string $geneSymbol): HgncRecord;

    public function fetchPreviousSymbol(string $geneSymbol): HgncRecord;

    public function fetchHgncId(string $hgncId): HgncRecord;

    public function fetchCustomDownload(array $params): Collection;
}
