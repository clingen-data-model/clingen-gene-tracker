<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = [
        'gene_symbol',
        'expert_panel_id',
        'curator_id',
        'notes',
        'mondo_id',
        'curation_date',
        'disease_entity_notes'
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

    public function scopeGene($query, $geneSymbol)
    {
        return $query->where('gene_symbol', $geneSymbol);
    }
}
