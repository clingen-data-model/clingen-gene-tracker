<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface HasAffiliation
{
    public function affiliation():BelongsTo;
}
