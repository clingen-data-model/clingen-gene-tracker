<?php

namespace App;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class ExpertPanel extends Model
{
    use RevisionableTrait, CrudTrait;

    protected $revisionCreationsEnabled = true;
    protected $fillable = [
        'name',
        'working_group_id'
    ];

    public function workingGroup()
    {
        return $this->belongsTo(WorkingGroup::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
