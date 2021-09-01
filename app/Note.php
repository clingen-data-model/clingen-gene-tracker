<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    use SoftDeletes;

    public $fillable = [
        'subject_type',
        'subject_id',
        'content',
        'topic',
        'author_type',
        'author_id'
    ];

    public $casts = [
        'id' => 'integer',
        'subject_id' => 'integer'
    ];

    /**
     * Get the subject that owns the Note
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the author that owns the Note
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author(): MorphTo
    {
        return $this->MorphTo();
    }
}
