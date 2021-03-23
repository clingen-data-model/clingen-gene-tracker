<?php

namespace App;

use Illuminate\Support\Str;
use JsonSerializable;

class ExternalServiceRecord implements JsonSerializable
{
    protected $attributes;

    public function __construct($attributes)
    {
        $this->attributes = (object) $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function jsonSerialize()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        $method = 'get'.Str::camel($key);
        if (method_exists($this, $method)) {
            return $this->$method($method, $this);
        }

        if (isset($this->attributes->{$key})) {
            return $this->attributes->{$key};
        }

        if (!isset($this->{$key})) {
            return null;
        }

        return $this->{$key};
    }
}
