<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StreamMessage extends Model
{
    protected $fillable = [
        'message',
        'topic',
        'sent_at',
        'error'
    ];
}
