<?php

namespace App\Hgnc;

use App\Gene;
use Carbon\Carbon;

class CustomDownloadImporter
{
    protected $client;
    private $defaultParams = [
        'col' => [
            'gd_hgnc_id',
            'gd_app_sym',
            'gd_app_name',
            'gd_status',
            'gd_prev_sym',
            'gd_aliases',
            'gd_pub_acc_ids',
            'gd_date_mod',
            'md_mim_id',
            'md_eg_id',
        ],
        'status' => [
            'Approved',
            'Entry Withdrawn',
        ],
        'hgnc_dbtag' => 'on',
        'order_by' => 'gd_app_sym_sort',
        'format' => 'text',
        'submit' => 'submit',
    ];

    public function __construct(HgncClientContract $client)
    {
        $this->client = $client;
    }

    public function import($params = null)
    {
        $params = $params ?? $this->defaultParams;

        $records = $this->client->fetchCustomDownload($params);

        $hgncIdToUpdatedAt = Gene::select('hgnc_id', 'updated_at')->get()->pluck('updated_at', 'hgnc_id');

        $records->each(function (HgncRecord $record) use ($hgncIdToUpdatedAt) {
            // $geneRecord = $genes->get($record->hgnc_id);
            // if ($geneRecord) {
            //     if (Carbon::parse($record->date_modifed)->lte($geneRecord->updated_at->startOfDay())) {
            //         return;
            //     }
            //     dump('update gene');

            //     return;
            // }

            // dump(($record->alias_symbols !== '') ? explode(',', $record->alias_symbols) : null);

            $prevSymbols = null;
            if ($record->previous_symbols !== '') {
                $prevSymbols = array_map(function ($sym) use ($record) {
                    return trim($sym);
                }, explode(',', $record->previous_symbols));
            }

            $aliasSymbols = null;
            if ($record->alias_symbols !== '') {
                $aliasSymbols = array_map(function ($sym) use ($record) {
                    return trim($sym);
                }, explode(',', $record->alias_symbols));
            }

            $newGene = Gene::create([
                'gene_symbol' => $record->approved_symbol,
                'hgnc_id' => $record->hgnc_id,
                'omim_id' => $record->omim_id ? $record->omim_id : null,
                'ncbi_gene_id' => $record->ncbi_gene_id,
                'hgnc_name' => $record->approved_name,
                'hgnc_status' => $record->status,
                'previous_symbols' => $prevSymbols,
                'alias_symbols' => $aliasSymbols,
            ]);
        });
    }
}
