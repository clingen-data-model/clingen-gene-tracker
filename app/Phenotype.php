<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phenotype extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mim_number',
        'name'
    ];

    public function curations()
    {
        return $this->belongsToMany(Curation::class);
    }
}
