<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * Do I treat this like an aggregateRoot or a Repository or is it a conflation of the two?
 *  * AggregateRoot:
 *      *.
 */
class Gene extends Model
{
    use SoftDeletes;
    use RevisionableTrait;

    public $fillable = [
        'gene_symbol',
        'hgnc_id',
        'omim_id',
        'ncbi_gene_id',
        'hgnc_name',
        'hgnc_status',
        'previous_symbols',
        'alias_symbols',
    ];

    protected $casts = [
        'previous_symbols' => 'array',
        'alias_symbols' => 'array',
    ];

    public $revisionCreationsEnabled = true;
}
