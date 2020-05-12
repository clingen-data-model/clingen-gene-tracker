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

    public $appends = [
        'gene',
        'condition',
        'moi',
        'affiliation',
        'affiliation_id'
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

    public function getAffiliationAttribute()
    {
        if (!isset($this->message_payload->performed_by->on_behalf_of)) {
            throw new InvalidArgumentException('The stream message '.$this->id.' does not include an affiliation id.');
        }

        return $this->message_payload->performed_by->on_behalf_of;
    }

    public function getAffiliationIdAttribute()
    {
        if (!isset($this->affiliation->id) || empty($this->affiliation->id)) {
            return null;
        }

        return $this->affiliation->id;
    }

    public function getGeneAttribute()
    {
        return $this->message_payload->gene_validity_evidence_level->genetic_condition->gene;
    }

    public function getConditionAttribute()
    {
        return $this->message_payload->gene_validity_evidence_level->genetic_condition->condition;
    }

    public function getMoiAttribute()
    {
        return $this->message_payload->gene_validity_evidence_level->genetic_condition->mode_of_inheritance;
    }
}
