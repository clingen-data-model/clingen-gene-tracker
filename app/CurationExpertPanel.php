<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CurationExpertPanel extends Pivot
{
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}