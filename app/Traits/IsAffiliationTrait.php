<?php

namespace App\Traits;

use App\Affiliation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait that implements IsAffiliation contract
 */
trait IsAffiliationTrait
{
    public function affiliation():BelongsTo
    {
        return $this->belongsTo(Affiliation::class);
    }
}
