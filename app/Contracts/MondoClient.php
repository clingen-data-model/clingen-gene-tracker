<?php

namespace App\Contracts;

use App\MondoRecord;

interface MondoClient
{
    public function fetchRecord($mondoId):MondoRecord;
}