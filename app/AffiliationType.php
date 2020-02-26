<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class AffiliationType extends Model
{
    use SoftDeletes;
    use RevisionableTrait;
    
    protected $revisionCreationsEnabled = true;
    protected $fillable = [
        'name'
    ];

    public function affiliations()
    {
        return $this->hasMany(Affiliation::class);
    }

}
