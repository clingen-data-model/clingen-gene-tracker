<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SearchService
{
    public function search($params);
}
