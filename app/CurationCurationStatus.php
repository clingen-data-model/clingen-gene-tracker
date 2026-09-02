<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CurationCurationStatus extends Pivot
{
    protected $fillable = [
        'curation_id',
        'curation_status_id',
        'status_date',
        'source',
        'source_event_key',
    ];

    protected $casts = [
        'status_date' => 'datetime'
    ];
}
