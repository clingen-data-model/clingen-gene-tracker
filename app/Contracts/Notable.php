<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Notable
{
    public function notes(): MorphMany;    
}