<?php

namespace App\Exceptions;

use Exception;

class UnmatchableCurationException extends GciSyncException
{
    protected $payload;

    public function __construct($data)
    {
        $this->message = 'Could not match incoming GCI record based on HGNC ID and MonDO ID.';
        $this->payload = $data;
    }

    public function getPayload()
    {
        return $this->payload;
    }
}
