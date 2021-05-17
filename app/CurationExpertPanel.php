<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CurationExpertPanel extends Pivot
{
    protected $dates = [
        'start_date',
        'end_date'
    ];
}