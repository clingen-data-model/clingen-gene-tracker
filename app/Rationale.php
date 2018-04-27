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

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }
}
