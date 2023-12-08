<?php

namespace App;

use App\DataExchange\Events\Created;
use App\Model;

class StreamMessage extends Model
{
    protected $fillable = [
        'message',
        'topic',
        'sent_at',
        'error'
    ];
    
    protected $dispatchesEvents = [
        'created' => Created::class,
    ];

    protected $casts = [
        'message' => 'array',
        'sent_at' => 'datetime',
    ];
    
    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeTopic($query, $topic)
    {
        return $query->where('topic', $topic);
    }
}
