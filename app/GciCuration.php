<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GciCuration extends Model
{
    public $fillable = [
        'gdm_uuid',
        'hgnc_id',
        'mondo_id',
        'moi_id',
        'classification_id',
        'status_id',
        'affiliation_id',
        'creator_uuid',
        'creator_email',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the status that owns the GciCuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CurationStatus::class, 'status_id', 'id');
    }

    /**
     * Get the classification that owns the GciCuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class, 'classification_id', 'id');
    }

    /**
     * Get the affiliation that owns the GciCuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function affiliation(): BelongsTo
    {
        return $this->belongsTo(Affiliation::class, 'affiliation_id', 'id');
    }

    /**
     * Get the moi that owns the GciCuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function moi(): BelongsTo
    {
        return $this->belongsTo(ModeOfInheritance::class, 'moi_id', 'id');
    }

    /**
     * Get the gene that owns the GciCuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gene(): BelongsTo
    {
        return $this->belongsTo(Gene::class, 'hgnc_id', 'hgnc_id');
    }

    /**
     * Get the curation associated with the GciCuration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function curation(): HasOne
    {
        return $this->hasOne(Curation::class, 'gdm_uuid', 'gdm_uuid');
    }
}
