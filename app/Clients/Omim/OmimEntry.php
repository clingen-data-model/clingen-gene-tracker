<?php

namespace App\Clients\Omim;

use App\Exceptions\OmimResponseException;

/**
 * Value object for an OMIM API entry.
 *
 * @property object      $phenotypeMap
 * @property int         $mimNumber
 * @property object      $titles
 * @property object|null $geneMap
 */
class OmimEntry implements OmimEntryContract
{
    protected $rawEntry;

    public function __construct($rawEntry)
    {
        $this->rawEntry = $rawEntry;
    }

    /**
     * Gets the phenotypeMapList whether inside geneMap or at root level.
     */
    public function getPhenotypeMapList()
    {
        if (isset($this->rawEntry->geneMap) && isset($this->rawEntry->geneMap->phenotypeMapList)) {
            return $this->rawEntry->geneMap->phenotypeMapList;
        }

        if (isset($this->rawEntry->phenotypeMapList) && count($this->rawEntry->phenotypeMapList) > 0) {
            return $this->rawEntry->phenotypeMapList;
        }

        throw new OmimResponseException('No phenotypeMapList on Entry', 422);
    }

    public function getPhenotypeName()
    {
        try {
            if (count($this->phenotypeMapList) == 0) {
                return $this->titles->preferredTitle;
            }

            return $this->phenotypeMapList[0]->phenotypeMap->phenotype;
        } catch (OmimResponseException $e) {
            return $this->titles->preferredTitle;
        }
    }

    public function getMappedGeneMimNumber()
    {
        try {
            if (count($this->phenotypeMapList) == 0) {
                return null;
            }

            return $this->phenotypeMapList[0]->phenotypeMap->mimNumber;
        } catch (OmimResponseException $e) {
            return null;
        }
    }

    public function getMoi()
    {
        try {
            if (count($this->phenotypeMapList) == 0) {
                return null;
            }

            return $this->phenotypeMapList[0]->phenotypeMap->phenotypeInheritance;
        } catch (OmimResponseException $e) {
            return null;
        }
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

    public function jsonSerialize()
    {
        return json_encode($this->rawEntry);
    }

    public function toJson()
    {
        return $this->jsonSerialize();
    }

    public function toArray()
    {
        return json_decode(json_encode($this->rawEntry), true);
    }

    public function __toString()
    {
        return json_encode($this->rawEntry);
    }

    public function isValid()
    {
        return count($this->getPhenotypeMapList()) > 0;
    }

    public function isInvalid()
    {
        return count($this->getPhenotypeMapList()) < 1;
    }
}
