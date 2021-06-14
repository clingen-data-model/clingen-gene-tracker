<?php

namespace App;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class ModeOfInheritance extends Model
{
    use CrudTrait;

    public $fillable = [
        'name',
        'abbreviation',
        'parent_id',
        'hp_uri',
        'curatable',
    ];

    /**
     * Get the parent that owns the ModeOfInheritance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(ModeOfInheritance::class, 'parent_id', 'id');
    }

    public function scopeCuratable($query)
    {
        return $query->where('curatable', 1);
    }

    public static function findByHpId($hpId)
    {
        if (substr($hpId, 0, 3) != 'HP:') {
            $hpId = 'HP:'.$hpId;
        }

        return static::query()->where('hp_id', $hpId)->first();
    }
}
