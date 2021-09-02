<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Model;

class Email extends Model
{
    use CrudTrait;

    protected $guarded = [];
    protected $casts = [
        'from' => 'array',
        'sender' => 'array',
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'reply_to' => 'array'
    ];

    public function scopeFrom($query, $from)
    {
        return $query->where('from_address', $from);
    }

    public function scopeLikeFrom($query, $from)
    {
        return $query->where('from_address', 'LIKE', '%'.$from.'%');
    }
    
    public function scopeTo($query, $to)
    {
        return $query->where('to', $to);
    }

    public function scopeLikeTo($query, $to)
    {
        return $query->where('to', 'LIKE', '%'.$to.'%');
    }
}
