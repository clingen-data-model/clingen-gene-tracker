<?php

namespace App\Exceptions;

use Exception;

class DuplicateBulkCurationException extends Exception
{
    public $duplicates = [];

    public function __construct($duplicates)
    {
        $this->duplicates = $duplicates;
        parent::__construct('There are genes in your spreadsheet that already have curations in the GeneTracker.', 400);
    }
}
