<?php

namespace App;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class WorkingGroup extends Model
{
    use SoftDeletes;
    use RevisionableTrait;
    use CrudTrait;

    protected $revisionCreationsEnabled = true;
    protected $hidden = ['deleted_at'];

    protected $fillable = [
        'name',
    ];

    public function expertPanels()
    {
        return $this->hasMany(ExpertPanel::class);
    }
}
