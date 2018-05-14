<?php

namespace App;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class Topic extends Model
{
    use CrudTrait;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        'gene_symbol',
        'expert_panel_id',
        'curator_id',
        'notes',
        'mondo_id',
        'curation_date',
        'disease_entity_notes',
        'topic_status_id',
        'curation_type_id',
        'rationale_id',
        'rationale_other',
        'rationale_notes',
        'pmids',
    ];

    protected $dates = [
        'curation_date'
    ];

    protected $casts = [
        'pmids' => 'array'
    ];

    protected $with = [
        'currentStatus'
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function ($topic) {
            if (TopicStatus::count() > 0) {
                $topic->topicStatuses()->attach(TopicStatus::find(1));
            }
        });
    }

    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function curator()
    {
        return $this->belongsTo(User::class, 'curator_id');
    }

    public function phenotypes()
    {
        return $this->belongsToMany(Phenotype::class);
    }

    public function topicStatuses()
    {
        return $this->belongsToMany(TopicStatus::class)
                ->withPivot('created_at', 'updated_at')
                ->withTimestamps();
    }

    public function currentStatus()
    {
        return $this->topicStatuses()
                    ->orderBy('topic_topic_status.created_at', 'desc')
                    ->limit(1);
    }

    public function getCurrentStatusAttribute()
    {
        return $this->currentStatus()->first();
    }

    public function curationType()
    {
        return $this->belongsTo(CurationType::class);
    }

    public function rationale()
    {
        return $this->belongsTo(Rationale::class);
    }

    public function scopeGene($query, $geneSymbol)
    {
        return $query->where('gene_symbol', $geneSymbol);
    }
}
