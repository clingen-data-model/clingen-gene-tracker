<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IncomingStreamMessage extends Model
{
    public $fillable = [
        'topic',
        'partition',
        'payload',
        'error_code',
        'gdm_uuid',
        'offset',
        'timestamp',
        'key'
    ];

    protected $casts = [
        'payload' => 'object'
    ];

    public static function boot()
    {
        parent::boot();
    }
    
}
