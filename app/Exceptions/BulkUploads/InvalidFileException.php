<?php

namespace App\Exceptions\BulkUploads;

use Exception;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

class InvalidFileException extends InvalidArgumentException
{
    protected $validationErrors;

    public function __construct($validationErrors)
    {
        parent::__construct('Bulk upload file has rows with invalid data');
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
}
