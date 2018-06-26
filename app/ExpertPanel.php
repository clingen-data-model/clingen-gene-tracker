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

    public function curators()
    {
        return $this->belongsToMany(User::class)
                ->withPivot('can_edit_topics', 'is_curator', 'is_coordinator')
                ->wherePivot('is_curator', 1);
    }

    public function coordinators()
    {
        return $this->belongsToMany(User::class)
                ->withPivot('can_edit_topics', 'is_curator', 'is_coordinator')
                ->wherePivot('is_coordinator', 1);
    }

    public function addUser(User $user, $options = [])
    {
        $this->users()->attach($user->id, []);
    }
}
