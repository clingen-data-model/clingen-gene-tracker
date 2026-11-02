<?php
namespace App\Services\Curations;

use App\Curation;

/** Shared model mutation path for normal edits and the one-time GCI correction. */
class UpdateCuration
{
    public function update(Curation $curation, array $attributes): Curation
    {
        $curation->update($attributes);
        return $curation;
    }
}
