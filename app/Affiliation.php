<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

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

    public function type()
    {
        return $this->belongsTo(AffiliationType::class, 'affiliation_type_id');
    }
}
