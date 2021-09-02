<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Contracts\HasAffiliation;
use App\Traits\HasAffiliationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class WorkingGroup extends Model implements HasAffiliation
{
    use SoftDeletes;
    use RevisionableTrait;
    use CrudTrait;
    use HasAffiliationTrait;


    protected $revisionCreationsEnabled = true;
    protected $hidden = ['deleted_at'];

    protected $fillable = [
        'name',
        'affiliation_id'
    ];

    public function expertPanels()
    {
        return $this->hasMany(ExpertPanel::class);
    }
}
