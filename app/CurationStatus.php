<?php

namespace App;

use App\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class CurationStatus extends Model
{
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        'name'
    ];

    protected $touches = ['curations'];

    public function curations()
    {
        return $this->belongsToMany(Curation::class);
    }
}
