<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disease extends Model
{
    protected $fillable = [
        'mondo_id',
        'name',
        'is_obsolete',
        'replaced_by'
    ];

    protected $casts = [
        'is_obsolete' => 'bool'
    ];

    /**
     * Get the replacedBy that owns the Disease
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(Disease::class, 'replaced_by', 'mondo_id');
    }

    /**
     * Get the curations that owns the Disease
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function curations(): HasMany
    {
        return $this->hasMany(Curation::class, 'mondo_id', 'mondo_id');
    }

    /**
     * SCOPES
     */
    public function scopeObsolete($query)
    {
        return $query->where('is_obsolete', 1);
    }

    /**
     * MUTATORS
     */
    public function setIsObsoleteAttribute($value)
    {
        $this->attributes['is_obsolete'] = (bool)$value;
    }
}
