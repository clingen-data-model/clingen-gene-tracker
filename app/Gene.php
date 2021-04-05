<?php

namespace App;

use Illuminate\Support\Facades\Event;
use App\Events\Genes\GeneSymbolChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Do I treat this like an aggregateRoot or a Repository or is it a conflation of the two?
 *  * AggregateRoot:
 *      *.
 */
class Gene extends Model
{
    use SoftDeletes;
    use RevisionableTrait;

    public $incrementing = false;
    protected $primaryKey = 'hgnc_id';

    public $fillable = [
        'gene_symbol',
        'hgnc_id',
        'omim_id',
        'ncbi_gene_id',
        'hgnc_name',
        'hgnc_status',
        'previous_symbols',
        'alias_symbols',
        'date_approved',
        'date_modified',
        'date_symbol_changed',
        'date_name_changed'
    ];

    protected $dates = [
        'date_modified',
        'date_approved',
    ];

    protected $casts = [
        'previous_symbols' => 'array',
        'alias_symbols' => 'array',
    ];

    public $revisionCreationsEnabled = true;

    public static function boot()
    {
        parent::boot();

        static::updated(function ($model) {
            if ($model->isDirty('gene_symbol')) {
                event(new GeneSymbolChanged($model, $model->getOriginal('gene_symbol')));
            }
        });
    }

    // Relations
    /**
     * The phenotypes that belong to the Gene
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function phenotypes(): BelongsToMany
    {
        return $this->belongsToMany(Phenotype::class, 'gene_phenotype', 'hgnc_id', 'phenotype_id')
            ->withTimestamps();
    }

    // Access methods

    public static function findBySymbol(String $symbol)
    {
        return static::where('gene_symbol', $symbol)->first();
    }

    public static function findByOmimId(Int $id)
    {
        return static::where('omim_id', $id)->first();
    }

    public static function findByPreviousSymbol(String $symbol)
    {
        return static::whereJsonContains('previous_symbols', $symbol)->first();
    }
}
