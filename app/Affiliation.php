<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    use CrudTrait;

    protected $revisionCreationsEnabled = true;
    
    protected $fillable = [
        'clingen_id',
        'name',
        'short_name',
        'affiliation_type_id',
        'parent_id'
    ];

    protected $with = [
        'type'
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AffiliationType::class, 'affiliation_type_id');
    }

    public function expertPanel()
    {
        return $this->hasOne(ExpertPanel::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class, 'parent_id');
    }
    

    public static function findByClingenId($clingenId)
    {
        return static::where('clingen_id', $clingenId)->first();
    }
    

    public function getDescriptiveNameAttribute()
    {
        $typeName = $this->type->name ?? '';
        return trim($this->name . ($typeName ? ' ' . strtoupper($typeName) : ''));
    }

    public function getDescriptiveShortNameAttribute()
    {
        $typeName = $this->type->name ?? '';
        return trim($this->short_name . ($typeName ? ' ' . strtoupper($typeName) : ''));
    }

}
