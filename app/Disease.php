<?php

namespace App;

use App\Events\Disease\DiseaseNameChanged;
use App\Events\Disease\MondoTermObsoleted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disease extends Model
{
    protected $fillable = [
        'mondo_id',
        'name',
        'is_obsolete',
        'replaced_by',
    ];

    protected $casts = [
        'is_obsolete' => 'bool',
    ];

    public static function boot()
    {
        parent::boot();

        static::updated(function ($disease) {
            if ($disease->isDirty('name')) {
                event(new DiseaseNameChanged($disease, $disease->getOriginal('name')));
            }
            if ($disease->isDirty('is_obsolete')) {
                if ($disease->is_obsolete) {
                    event(new MondoTermObsoleted($disease));
                }
            }
        });
    }

    /**
     * Get the replacedBy that owns the Disease
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(Disease::class, 'replaced_by', 'mondo_id');
    }

    /**
     * Get the curations that owns the Disease
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

    public function scopeMondoId($query, $mondoId)
    {
        return $query->where('mondo_id', $mondoId);
    }

    public static function findByMondoId($mondoId)
    {
        return static::mondoId($mondoId)->first();
    }

    public static function findByMondoIdOrFail($mondoId)
    {
        return static::mondoId($mondoId)->firstOrFail();
    }

    public function scopeSearch($query, $string)
    {
        return $query->where('name', 'like', '%'.$string.'%')
            ->orWhere('mondo_id', 'like', '%'.$string.'%');
    }
}
