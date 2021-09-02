<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Contracts\HasAffiliation;
use App\Traits\HasAffiliationTrait;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertPanel extends Model implements HasAffiliation
{
    use RevisionableTrait;
    use CrudTrait;
    use HasAffiliationTrait;

    protected $revisionCreationsEnabled = true;
    protected $fillable = [
        'name',
        'working_group_id',
        'affiliation_id',
    ];

    public function workingGroup()
    {
        return $this->belongsTo(WorkingGroup::class);
    }

    public function curations()
    {
        return $this->hasMany(Curation::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
                ->withPivot('can_edit_curations', 'is_curator', 'is_coordinator');
    }

    public function curators()
    {
        return $this->belongsToMany(User::class)
                ->withPivot('can_edit_curations', 'is_curator', 'is_coordinator')
                ->wherePivot('is_curator', 1);
    }

    public function coordinators()
    {
        return $this->belongsToMany(User::class)
                ->withPivot('can_edit_curations', 'is_curator', 'is_coordinator')
                ->wherePivot('is_coordinator', 1);
    }

    public function affiliation(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class, 'affiliation_id', 'id');
    }

    public function getFileSafeNameAttribute()
    {
        return preg_replace('/[\\:\\/\*\?"<>\| ]/', '-', $this->name);
    }

    public function addCoordinator(User $user)
    {
        $this->users()->attach($user->id, ['is_coordinator' => 1]);
    }

    public function getUuidAttribute()
    {
        return $this->id;
    }
}
