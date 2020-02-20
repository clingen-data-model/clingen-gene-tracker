<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface IsAffiliation
{
    public function affiliation():BelongsTo;
}
