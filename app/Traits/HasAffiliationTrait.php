<?php

namespace App\Traits;

use App\Affiliation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait that implements HasAffiliation contract
 */
trait HasAffiliationTrait
{
    public function affiliation():BelongsTo
    {
        return $this->belongsTo(Affiliation::class);
    }
}
