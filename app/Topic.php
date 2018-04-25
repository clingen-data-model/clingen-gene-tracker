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
        'curation_type_id'
    ];

    protected $dates = [
        'curation_date'
    ];

    public static function boot()
    {
        parent::boot();
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

    public function topicStatus()
    {
        return $this->belongsTo(TopicStatus::class);
    }

    public function curationType()
    {
        return $this->belongsTo(CurationType::class);
    }

    public function scopeGene($query, $geneSymbol)
    {
        return $query->where('gene_symbol', $geneSymbol);
    }
}
