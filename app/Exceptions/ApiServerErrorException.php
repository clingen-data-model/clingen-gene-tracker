<?php

namespace App\Exceptions;

use Exception;

class ApiServerErrorException extends Exception
{
    protected $api;

    protected $url;

    protected $previous;

    public function __construct($api, $url, $previous = null)
    {
        $this->api = $api;
        $this->url = $url;
        $this->previous = $previous;

        $message = 'There was a problem accessing the '.$this->api.' API at '.$this->url;
        parent::__construct($message, 500, $previous);
    }

    
}
