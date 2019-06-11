<?php

namespace App\Contracts;

use GuzzleHttp\Client;

interface OmimClient
{
    public function __construct($client = null);

    public function getEntry($mimNumber);

    public function search($searchData);

    public function geneSymbolIsValid($geneSymbol);

    public function getGenePhenotypes($geneSymbol);
}
