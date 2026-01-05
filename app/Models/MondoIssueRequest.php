<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Curation;

class MondoIssueRequest extends Model
{
    protected $fillable = [
        'uuid',
        'curation_id',
        'request_type',
        'title',
        'body_markdown',
        'payload_json',
        'github_owner',
        'github_repo',
        'github_issue_number',
        'github_issue_url',
        'github_state',
        'status',
        'last_error',
        'submitted_at',
        'last_synced_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'submitted_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function curation(): BelongsTo
    {
        return $this->belongsTo(Curation::class);
    }
}
