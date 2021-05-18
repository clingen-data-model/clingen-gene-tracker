<?php

namespace App;

use App\Events\StreamMessages\Created;
use Illuminate\Database\Eloquent\Model;

class StreamMessage extends Model
{
    protected $fillable = [
        'message',
        'topic',
        'sent_at',
        'error'
    ];
    
    protected $dates = [
        'sent_at'
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
    ];

    protected $casts = [
        'message' => 'array'
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
