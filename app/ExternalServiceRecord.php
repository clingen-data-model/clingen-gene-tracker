<?php

namespace App;

class ExternalServiceRecord
{
    protected $attributes;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        $method = 'get'.camel_case($key);
        if (method_exists($this, $method)) {
            return $this->$method($method, $this);
        }

        if (isset($this->attributes->{$key})) {
            return $this->attributes->{$key};
        }

        return $this->{$key};
    }
}
