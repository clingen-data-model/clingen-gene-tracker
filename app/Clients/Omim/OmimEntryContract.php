<?php

namespace App\Clients\Omim;

interface OmimEntryContract
{
    public function getPhenotypeMapList();
    public function jsonSerialize();
    public function toJson();
    public function toArray();
    public function __toString();
    public function isValid();
}
