<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CurationExpertPanel extends Pivot
{
    protected $fillable = [
        'curation_id',
        'expert_panel_id',
        'start_date',
        'end_date',
        'source',
        'source_event_key',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}
