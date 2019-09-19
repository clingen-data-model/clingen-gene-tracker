<?php

namespace App;

final class HgncRecord
{
    protected $attributes;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    public function getHgncId() {
        return (int)substr($this->attributes->hgnc_id, 5);
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function __get($key) {
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
