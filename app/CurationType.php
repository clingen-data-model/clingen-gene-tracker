<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class CurationType extends Model
{
    use CrudTrait;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;
    protected $fillable = ['name', 'description'];
    protected $touches = ['curations'];
    protected $hidden = ['created_at', 'updated-at'];


    public function curations()
    {
        return $this->hasMany(Curation::class);
    }
}
