<?php

namespace App\Exceptions\BulkUploads;

use Exception;

class InvalidRowException extends \InvalidArgumentException
{
    protected $rowData;

    protected $validationErrors;

    public function __construct($rowData, $validationErrors)
    {
        parent::__construct('Invalid data in upload row.');
        $this->rowData = $rowData;
        $this->validationErrors = $validationErrors;
    }

    public function getRowData()
    {
        return $this->rowData;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
}
