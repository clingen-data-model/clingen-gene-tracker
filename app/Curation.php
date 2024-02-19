<?php

namespace App;

use Carbon\Carbon;
use App\Traits\HasUuid;
use App\Traits\HasNotes;
use App\Contracts\Notable;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Events\Curation\Saved;
use App\Events\Curation\Saving;
use App\Events\Curation\Created;
use App\Events\Curation\Deleted;
use App\Events\Curation\Updated;
use App\Jobs\Curations\SetOwner;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use App\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Classification $currentClassificiation
 * @property CurationStatus $currentStatus
 * @property string numericMondoId
 *
 **/
class Curation extends Model implements Notable
{
    use CrudTrait;
    use RevisionableTrait;
    use HasUuid;
    use SoftDeletes;
    use HasNotes;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        // 'uuid',
        'gdm_uuid',
        'gene_symbol',
        'hgnc_name',
        'hgnc_id',
        'expert_panel_id',
        'curation_status_id',
        'curator_id',
        'curation_notes',
        'mondo_id',
        'curation_date',
        'disease_entity_notes',
        'curation_type_id',
        'rationale_other',
        'rationale_notes',
        'pmids',
        'moi_id',
        'affiliation_id',
    ];

    protected $casts = [
        'pmids' => 'array',
        'curation_date' => 'datetime',
    ];

    protected $with = [
        // 'currentStatus'
        'disease'
    ];

    protected $dispatchesEvents = [
        'saving' => Saving::class,
        'saved' => Saved::class,
        'created' => Created::class,
        'updated' => Updated::class,
        'deleted' => Deleted::class,
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function ($curation) {
            if (CurationStatus::count() > 0 && !config('app.bulk_uploading')) {
                AddStatus::dispatch($curation, CurationStatus::find(1), $curation->created_at->format("Y-m-d H:i:s"));
                SetOwner::dispatch($curation, $curation->expert_panel_id, $curation->created_at);
            }
        });
    }

    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function expertPanels()
    {
        return $this->belongsToMany(ExpertPanel::class)
                ->using(CurationExpertPanel::class)
                ->withPivot(['start_date', 'end_date'])
                ->withTimestamps();
    }

    public function affiliation()
    {
        return $this->belongsTo(Affiliation::class);
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
                ->orderBy('curation_curation_status.status_date')
                ->orderBy('curation_curation_status.curation_status_id')
                ->withTimestamps();
    }

    public function statuses()
    {
        return $this->curationStatuses();
    }

    public function currentStatus()
    {
        return $this->belongsTo(CurationStatus::class, 'curation_status_id', 'id');
    }
    
    public function curationType()
    {
        return $this->belongsTo(CurationType::class);
    }

    public function rationales()
    {
        return $this->belongsToMany(Rationale::class);
    }

    public function classifications()
    {
        return $this->belongsToMany(Classification::class)
                ->withPivot('id', 'classification_date')
                ->withTimestamps()
                ->orderBy('classification_date', 'desc')
                ->orderBy('classification_curation.id', 'desc')
                ->using(CurationClassification::class);
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    public function modeOfInheritance(): BelongsTo
    {
        return $this->belongsto(ModeOfInheritance::class, 'moi_id', 'id');
    }

    public function moi(): BelongsTo
    {
        return $this->modeOfInheritance();
    }

    public function gene(): BelongsTo
    {
        return $this->belongsTo(Gene::class, 'hgnc_id', 'hgnc_id');
    }

    public function gciCuration(): BelongsTo
    {
        return $this->belongsTo(GciCuration::class, 'gdm_uuid', 'gdm_uuid');
    }

    public function disease()
    {
        return $this->belongsTo(Disease::class, 'mondo_id', 'mondo_id');
    }
    

    /**
     * Get all of the incomingStreamMessages for the GciCuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function incomingStreamMessages(): HasMany
    {
        return $this->hasMany(IncomingStreamMessage::class, 'gdm_uuid', 'gdm_uuid');
    }


    /**
     * ACCESSORS.
     */
    public function getCurrentClassificationAttribute()
    {
        $query = $this->classifications();

        return $query->first() 
            ?? new Classification();
    }

    public function setExpertPanelIdAttribute($value)
    {
        if (isset($this->attributes['expert_panel_id']) && $value == $this->attributes['expert_panel_id']) {
            $this->attributes['expert_panel_id'] = $value;
            return;
        }

        if (!is_null($this->id)) {
            $backtrace = collect(debug_backtrace());
            if (!$backtrace->pluck('class')->contains(SetOwner::class)) {
                $backtrace = $backtrace->map(function ($step) {
                    return $step['file'].":".$step['line'];
                })->toArray();
    
                \Log::warning('You shouldn\'t update the curation\s expert_panel_id attribute directly.  Use the App\Jobs\Curations\SetOwner job to add a new owner.', $backtrace);
            }
            
        }
        $this->attributes['expert_panel_id'] = $value;
    }

    public function getCurrentStatusDateAttribute()
    {
        $lastStatus = $this->statuses->where('id', $this->curation_status_id)->last();
        if ($lastStatus) {
            return $lastStatus->pivot->status_date;
        }
        return null;
    }

    public function getMondoNameAttribute()
    {
        return ($this->disease) ? $this->disease->name : null;
    }
    
    

    /**
     * SCOPES.
     */
    public function scopeGene($query, $geneSymbol)
    {
        return $query->where('gene_symbol', $geneSymbol);
    }

    public function scopeHgncId($query, $hgncId)
    {
        if (is_array($hgncId)) {
            return $query->whereIn(
                'hgnc_id',
                array_map(
                    function ($item) {
                        return preg_replace('/HGNC:/i', '', trim($item));
                    },
                    $hgncId
                )
            );
        }
        $formattedId = preg_replace('/HGNC:/i', '', trim($hgncId));

        return $query->where('hgnc_id', $formattedId);
    }

    public function scopeMondoId($query, $mondoId)
    {
        if (is_array($mondoId)) {
            return $query->whereIn(
                'mondo_id',
                array_map(
                    function ($item) {
                        return 'MONDO:'.str_pad(trim($item), 7, '0', STR_PAD_LEFT);
                    },
                    $mondoId
                )
            );
        }

        $mondoId = trim($mondoId);
        if (is_numeric($mondoId)) {
            $mondoId = 'MONDO:'.str_pad($mondoId, 7, '0', STR_PAD_LEFT);
        }

        return $query->where('mondo_id', $mondoId);
    }

    public function loadForMessage()
    {
        $this->load('curationType', 'currentStatus', 'rationales', 'curator', 'phenotypes', 'modeOfInheritance', 'expertPanel');

        return $this;
    }

    public function scopeHgncAndMondo($query, $hgncId, $mondoId)
    {
        $hgncId = preg_replace('/HGNC:/', '', $hgncId);

        return $query->where([
            'hgnc_id' => $hgncId,
            'mondo_id' => $mondoId,
        ]);
    }

    public function scopeNoGdmUuid($query)
    {
        return $query->whereNull('gdm_uuid');
    }

    public function scopeHasUuid($query)
    {
        return $query->whereNotNull('gdm_uuid');
    }

    public function getNumericMondoIdAttribute()
    {
        if (is_null($this->mondo_id)) {
            return null;
        }

        return preg_replace('/mondo: ?(\d+)/i', '$1', $this->mondo_id);
    }

    public function getExcludedPhenotypesAttribute()
    {
        if (!$this->gene) {
            return collect();
        }
        $curationPhenos = $this->phenotypes()->get();
        return $this->gene->phenotypes()
                ->whereNotIn('mim_number', $curationPhenos->pluck('mim_number')->toArray())
                ->select('mim_number', 'name')
                ->orderBy('mim_number')
                ->get();
    }
    

    /**
     * MUTATORS.
     */
    public function setGeneSymbolAttribute($value)
    {
        $this->attributes['gene_symbol'] = trim($value);
    }

    public function setMondoIdAttribute($value)
    {
        $formattedValue = $value;
        if (is_numeric($value)) {
            $formattedValue = 'MONDO:'.$value;
        }

        if (preg_match('/mondo:/i', $value)) {
            $formattedValue = strtoupper($value);
        }

        $this->attributes['mondo_id'] = $formattedValue;
    }

    /**
     * DOMAIN METHODS.
     */
    
    public static function findByUuid($uuid)
    {
        return static::where('uuid', $uuid)->first();
    }

    public static function findByGdmUuid($uuid)
    {
        return static::where('gdm_uuid', $uuid)->first();
    }

    public static function findByAnyId($curationId): ?self
    {
        $curation = null;
        
        if (is_numeric($curationId)) {
            $curation = Curation::find($curationId);
        }

        if (!$curation) {
            $curation = Curation::findByUuid($curationId);
        }

        if (!$curation) {
            $curation = Curation::findByGdmUuid($curationId);
        }

        return $curation;
    }

    public static function findByHgncAndMondo($hgncId, $mondoId)
    {
        $hgncId = preg_replace('/HGNC:/', '', $hgncId);

        return static::where([
            'hgnc_id' => $hgncId,
            'mondo_id' => $mondoId,
        ])->first();
    }

    public function addUpload(Upload $upload): void
    {
        $this->uploads()->save($upload);
    }

    public function removeUpload(Upload $upload): void
    {
        $upload->delete();
    }

    public function addPhenotype(Phenotype $phenotype): void
    {
        $this->phenotypes()->save($phenotype);
    }
}
