<?php

namespace App;

use App\Model;
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

        public function getFartsAttribute()
        {
            return $this->attributes['name'];
        }
    

}
