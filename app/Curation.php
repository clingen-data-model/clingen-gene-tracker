<?php

namespace App;

use Backpack\CRUD\CrudTrait;
use App\Events\Curation\Created;
use App\Events\Curation\Deleted;
use App\Events\Curation\Updated;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class Curation extends Model
{
    use CrudTrait;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        'gene_symbol',
        'hgnc_name',
        'hgnc_id',
        'expert_panel_id',
        'curator_id',
        'notes',
        'mondo_id',
        'mondo_name',
        'curation_date',
        'disease_entity_notes',
        'curation_status_id',
        'curation_type_id',
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
        // 'currentStatus'
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
        'updated' => Updated::class,
        'deleted' => Deleted::class,
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function ($curation) {
            if (CurationStatus::count() > 0 && !config('app.bulk_uploading')) {
                $curation->curationStatuses()->attach(CurationStatus::find(1));
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

    public function curationStatuses()
    {
        return $this->belongsToMany(CurationStatus::class)
                ->using(CurationCurationStatus::class)
                ->withPivot('id', 'status_date', 'created_at', 'updated_at')
                ->withTimestamps();
    }

    public function statuses()
    {
        return $this->curationStatuses();
    }

    public function getCurrentStatusAttribute()
    {
        return $this->curationStatuses->sortByDesc('pivot.status_date')->first();
    }

    public function getNumericMondoId()
    {
        if (is_null($this->mondo_id)) {
            return null;
        }
        return substr($this->mondo_id, 6);
    }

    public function curationType()
    {
        return $this->belongsTo(CurationType::class);
    }

    public function rationales()
    {
        return $this->belongsToMany(Rationale::class);
    }

    public function scopeGene($query, $geneSymbol)
    {
        return $query->where('gene_symbol', $geneSymbol);
    }

    public function loadForMessage()
    {
        $this->load('curationType', 'curationStatuses', 'rationales', 'curator', 'phenotypes');
        return $this;
    }
    
}
