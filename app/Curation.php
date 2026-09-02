<?php

namespace App;

use Carbon\Carbon;
use App\Curations\CurationField;
use App\Traits\HasUuid;
use App\Traits\HasNotes;
use App\Contracts\Notable;
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
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property Classification $currentClassificiation
 * @property CurationStatus $currentStatus
 * @property string numericMondoId
 *
 **/
class Curation extends Model implements Notable
{
    use RevisionableTrait;
    use HasUuid;
    use SoftDeletes;
    use HasNotes;

    /**
     * Revisionable is a passive attribute-level audit trail here, nothing more.
     * It cannot back the field history in curation_curation_status /
     * classification_curation / curation_expert_panel: it stamps its own
     * created_at rather than an event date, never sees pivot writes, has no
     * idempotency key, and records a null user on every queue and console path.
     */
    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        // 'uuid',
        'gdm_uuid',
        'gci_event_watermark',
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
        'archived_at',
        'archive_reason',
        'gcex_url'
    ];

    protected $casts = [
        'pmids' => 'array',
        'curation_date' => 'datetime',
        'archived_at' => 'datetime',
        'gci_event_watermark' => 'datetime',
    ];

    protected $with = [
        // 'currentStatus'
        'disease'
    ];

    protected $appends = [
        'is_archived'
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
                // Keying both on the curation itself means this hook can fire more
                // than once without producing a second row for either field.
                AddStatus::dispatchSync(
                    $curation,
                    CurationStatus::find(1),
                    $curation->created_at->format("Y-m-d H:i:s"),
                    'created',
                    'created:'.$curation->id
                );
                SetOwner::dispatchSync(
                    $curation,
                    $curation->expert_panel_id,
                    $curation->created_at,
                    null,
                    'created',
                    'created:'.$curation->id
                );
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
                ->orderBy('curation_curation_status.status_date', 'DESC')
                ->orderBy('curation_curation_status.curation_status_id', 'DESC')
                ->orderBy('curation_curation_status.id', 'DESC')
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


    public function getCurrentStatusDateAttribute()
    {
        $currentStatus = $this->statuses->where('id', $this->curation_status_id)->first();
        if ($currentStatus) {
            return $currentStatus->pivot->status_date;
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
                ->select('phenotypes.mim_number', 'phenotypes.name')
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

    // Archived Curation linked to it. LUMPING/SPLITTING depends on the presence of this relationship linkedArchivedCurations
    protected function isArchived(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => ! empty($attributes['archived_at']),
        );
    }

    public function linkedArchivedCurations(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'curation_archive_links',
            'curation_id',
            'archived_curation_id'
        )->withTimestamps();
    }

    public function linkedCurrentCurations(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'curation_archive_links',
            'archived_curation_id',
            'curation_id'
        )->withTimestamps();
    }

    /**
     * The value a tracked field held as of $date, according to its history.
     *
     * This is the query the "is this already the current value?" checks in
     * AddStatus, AddClassification and SetOwner each used to answer their own way.
     */
    public function valueAt(CurationField $field, $date): ?int
    {
        $value = \DB::table($field->historyTable())
            ->where('curation_id', $this->getKey())
            ->where($field->dateColumn(), '<=', Carbon::parse($date)->format('Y-m-d H:i:s'))
            ->orderByDesc($field->dateColumn())
            ->orderByDesc($field->tiebreakColumn())
            ->orderByDesc('id')
            ->value($field->valueColumn());

        return $value === null ? null : (int) $value;
    }

    /**
     * The newest status history row. Prefer this over positional access on the
     * curationStatuses() relation, whose ordering is for display.
     */
    public function latestStatusRow()
    {
        return $this->curationStatuses()
            ->reorder()
            ->orderByDesc('curation_curation_status.status_date')
            ->orderByDesc('curation_curation_status.curation_status_id')
            ->orderByDesc('curation_curation_status.id')
            ->first();
    }

    public function classificationBefore($date): Classification
    {
        $date = Carbon::parse($date);

        return $this->classifications()
            ->wherePivot('classification_date', '<', $date->format('Y-m-d H:i:s'))
            ->orderBy('classification_curation.classification_date', 'desc')
            ->orderBy('classification_curation.id', 'desc')
            ->first()
            ?? new Classification();
    }
}
