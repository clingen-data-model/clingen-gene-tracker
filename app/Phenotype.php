<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class Phenotype extends Model
{
    use SoftDeletes;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        'mim_number',
        'name',
        'omim_entry',
        'omim_status',
        'moved_mim_number'
    ];

    protected $touches = ['curations'];

    protected $casts = [
        'omim_entry' => 'array'
    ];

    public function curations()
    {
        return $this->belongsToMany(Curation::class);
    }

    public function scopeMimNumber($query, $mimNumber)
    {
        return $query->where('mim_number', $mimNumber);
    }

    public static function findByMimNumber($mimNumber)
    {
        return static::mimNumber($mimNumber)->first();
    }
}
