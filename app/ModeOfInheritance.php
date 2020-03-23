<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModeOfInheritance extends Model
{
    public static function findByHpId($hpId)
    {
        if (substr($hpId, 0, 3) != 'HP:') {
            $hpId = 'HP:'.$hpId;
        }

        return static::query()->where('hp_id', $hpId)->first();
    }
}
