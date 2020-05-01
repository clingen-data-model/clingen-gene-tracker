<?php

namespace App\Clients\Omim;

class OmimEntry
{
    protected $rawEntry;

    public function __construct($rawEntry)
    {
        $this->rawEntry = $rawEntry[0]->entry;
    }

    public function getPhenotypeMapList()
    {
        if (isset($this->rawEntry->geneMap) && isset($this->rawEntry->geneMap->phenotypeMapList)) {
            return $this->rawEntry->geneMap->phenotypeMapList;
        }

        if (isset($this->rawEntry->phenotypeMapList)) {
            return $this->rawEntry->phenotypeMapList;
        }

        throw new \Exception("No phenotypeMapList on Entry");
    }

    public function __get($key)
    {
        if (method_exists($this, 'get'.ucfirst(camel_case($key)))) {
            $methodName = 'get'.ucfirst(camel_case($key));
            return $this->$methodName();
        }
        if (in_array($key, array_keys(get_object_vars($this->rawEntry)))) {
            return $this->rawEntry->{$key};
        }
    }
}
