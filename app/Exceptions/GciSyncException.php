<?php

namespace App\Exceptions;

use Exception;

class GciSyncException extends Exception
{
    protected $data = [];
        
    public function addData($data)
    {
        array_push($this->data, $data);
    }

    public function hasData()
    {
        return count($this->data) > 0;
    }

    public function getData()
    {
        return $this->data;
    }
}
