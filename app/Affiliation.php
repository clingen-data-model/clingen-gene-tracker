<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * Eloquent Model: 'Affiliation'
 *
 * @property string $descriptiveName
 * @property string $descriptiveShortName
 */
class Affiliation extends Model
{
    use SoftDeletes;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;
    
    protected $fillable = [
        'clingen_id',
        'name',
        'short_name',
        'affiliation_type_id',
    ];

    protected $with = [
        'type'
    ];

    public function type()
    {
        return $this->belongsTo(AffiliationType::class, 'affiliation_type_id');
    }

    public function expertPanel()
    {
        return $this->hasOne(ExpertPanel::class);
    }

    public function parent()
    {
        return $this->belongsTo(Affiliation::class, 'parent_id');
    }
    

    public static function findByClingenId($clingenId)
    {
        return static::where('clingen_id', $clingenId)->first();
    }
    

    public function getDescriptiveNameAttribute()
    {
        return $this->name.' ('.$this->type->name.')';
    }

    public function getDescriptiveShortNameAttribute()
    {
        $name = (empty($this->short_name)) ? $this->name : $this->short_name;
        return $this->short_name.' ('.$this->type->name.')';
    }
}
