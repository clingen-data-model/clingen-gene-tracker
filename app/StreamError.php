<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StreamError extends Model
{
    public $fillable = [
        'type',
        'message_payload',
        'direction',
        'notification_sent_at'
    ];

    public $dates = ['notification_sent_at'];

    public $casts = [
        'message_payload' => 'object'
    ];
}
