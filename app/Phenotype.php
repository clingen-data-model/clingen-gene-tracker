<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phenotype extends Model
{
    use SoftDeletes;

    protected $fillable = ['mim_number'];

    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }
}
