<?php

namespace App\Contracts;

use App\Clients\Omim\OmimEntry;
use GuzzleHttp\Client;

interface OmimClient
{
    public function __construct($client = null);

    public function getEntry($mimNumber);
    
    public function search($searchData);

    public function geneSymbolIsValid($geneSymbol);

    public function getGenePhenotypes($geneSymbol);
}
