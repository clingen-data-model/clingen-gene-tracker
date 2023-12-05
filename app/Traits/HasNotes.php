<?php

namespace App\Traits;

use App\Note;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasNotes
{
    /**
     * Get the notes that owns the HasNotes
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'subject');
    }
}
