<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModeOfInheritance extends Model
{
    public $fillable = [
        'name',
        'abbreviation',
        'parent_id',
        'hp_uri',
        'curatable',
    ];

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
