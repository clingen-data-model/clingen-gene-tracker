<?php

namespace App;

use App\Events\Phenotypes\OmimMovedPhenotype;
use App\Events\Phenotypes\OmimRemovedPhenotype;
use App\Events\Phenotypes\PhenotypeNameChanged;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class Phenotype extends Model
{
    use SoftDeletes;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        'mim_number',
        'name',
        'omim_entry',
        'omim_status',
        'moved_to_mim_number',
        'moi',
    ];

    protected $touches = ['curations'];

    protected $casts = [
        'omim_entry' => 'array',
        'moved_to_mim_number' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
        static::saved(function (Phenotype $phenotype) {
            if ($phenotype->isDirty('omim_status')) {
                if ($phenotype->omim_status === 'removed') {
                    event(new OmimRemovedPhenotype($phenotype));
                } elseif ($phenotype->omim_status === 'moved') {
                    event(new OmimMovedPhenotype($phenotype));
                }
            }
            if ($phenotype->isDirty('name')) {
                event(new PhenotypeNameChanged($phenotype));
            }
        });
    }

    public function curations()
    {
        return $this->belongsToMany(Curation::class);
    }

    /**
     * The genes that belong to the Phenotype
     */
    public function genes(): BelongsToMany
    {
        return $this->belongsToMany(Gene::class);
    }

    public function scopeMimNumber($query, $mimNumber)
    {
        return $query->where('mim_number', $mimNumber);
    }

    public static function findByMimNumber($mimNumber): ?self
    {
        return static::mimNumber($mimNumber)->first();
    }

    public static function findSoleByMimNumber($mimNumber)
    {
        static::mimNumber($mimNumber)->sole();
    }

    public function getMovedToPhenotypesAttribute(): Collection
    {
        return Phenotype::whereIn('mim_number', $this->moved_to_mim_number)->get() ?? new Collection();
    }
}
