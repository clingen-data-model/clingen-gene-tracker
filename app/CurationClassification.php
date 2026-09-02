<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CurationClassification extends Pivot
{
    protected $fillable = [
        'curation_id',
        'classification_id',
        'classification_date',
        'source',
        'source_event_key',
    ];

    protected $casts = [
        'classification_date' => 'datetime'
    ];
}
