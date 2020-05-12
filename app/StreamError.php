<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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

    public function affiliation()
    {
        return $this->belongsTo(Affiliation::class, 'message_payload->performed_by->on_behalf_of->id');
    }

    public function scopeUnsent($query)
    {
        return $query->whereNull('notification_sent_at');
    }
    
    public function markSent($dateTime = null)
    {
        $this->update(['notification_sent_at' => $dateTime ?? Carbon::now()]);
    }

    public function getAffiliationIdAttribute()
    {
        if (!isset($this->message_payload->performedBy->id) || empty($this->message_payload->performedBy->id)) {
            throw new InvalidArgumentException('The stream message '.$this->id.' does not include an affiliation id.');
        }

        return $this->message_payload->performedBy->id;
    }
}
