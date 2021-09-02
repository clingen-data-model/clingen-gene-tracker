<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class CurationStatus extends Model
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
        return $this->belongsToMany(Curation::class);
    }
}
