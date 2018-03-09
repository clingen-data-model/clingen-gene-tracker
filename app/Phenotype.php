<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phenotype extends Model
{
    use SoftDeletes;

    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }
}
