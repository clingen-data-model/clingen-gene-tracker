<?php

namespace App\Clients\Omim;

use Illuminate\Support\Str;

class NullOmimEntry implements OmimEntryContract
{
    public function getPhenotypeMapList()
    {
        return [];
    }

    public function jsonSerialize()
    {
        return null;
    }

    public function toJson()
    {
        return null;
    }

    public function toArray()
    {
        return [];
    }

    public function __toString()
    {
        return '';
    }

    public function __get($key)
    {
        if (method_exists($this, 'get'.ucfirst(Str::camel($key)))) {
            $methodName = 'get'.ucfirst(Str::camel($key));

            return $this->$methodName();
        }

        return null;
    }

    public function isValid()
    {
        return false;
    }
}
