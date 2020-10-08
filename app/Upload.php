<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class Upload extends Model
{
    use SoftDeletes;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        'curation_id',
        'name',
        'notes',
        'file_name',
        'file_path',
        'upload_category_id',
        'uploader_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($upload) {
            if (!$upload->uploader_id && \Auth::user()) {
                $upload->uploader_id = \Auth::user()->id;
            }
        });
    }

    public function curation()
    {
        return $this->belongsTo(Curation::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function category()
    {
        return $this->belongsTo(UploadCategory::class, 'upload_category_id');
    }

    public function scopeForCuration($query, $user)
    {
        if (is_int($user)) {
            return $query->where('curation_id', $user);
        }

        if (is_object($user) && get_class($user) == User::class) {
            return $query->where('curation_id', $user->id);
        }
    }

    public function scopeWithCategory($query, $category)
    {
        if (is_int($category)) {
            return $query->where('category_id', $category);
        }

        if (is_object($category) && get_class($category) == UploadCategory::class) {
            return $query->where('category_id', $category->id);
        }
    }
}
