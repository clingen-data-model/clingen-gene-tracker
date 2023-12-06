<?php

namespace App\Contracts;

interface OmimClient
{
    public function __construct($client = null);

    public function getEntry($mimNumber);

    public function search($searchData);

    public function paginatedSearch($searchData, $start = 0, $pageSize = 100);

    public function geneSymbolIsValid($geneSymbol);

    public function getGenePhenotypes($geneSymbol);
}
