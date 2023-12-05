<?php

namespace App;

use App\DataExchange\Events\Created;

class StreamMessage extends Model
{
    protected $fillable = [
        'message',
        'topic',
        'sent_at',
        'error',
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'message' => 'array',
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
