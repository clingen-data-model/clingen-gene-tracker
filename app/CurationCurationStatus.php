<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CurationCurationStatus extends Pivot
{
    protected $fillable = [
        'curation_id',
        'curation_status_id',
        'status_date'
    ];

    protected $dates = [
        'status_date'
    ];
}
