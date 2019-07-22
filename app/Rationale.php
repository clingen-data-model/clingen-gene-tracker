<?php

namespace App;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class Rationale extends Model
{
    use CrudTrait;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;
    protected $fillable = [
        'name'
    ];

    protected $touches = ['curations'];

    public function curations()
    {
        return $this->hasMany(Curation::class);
    }
}
