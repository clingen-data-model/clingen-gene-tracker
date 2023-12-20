<?php

namespace App\Hgnc;

use App\Gene;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Hgnc\HgncRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CustomDownloadImporter
{
    protected $client;
    private $defaultParams = [
        'col' => [
            'md_eg_id',
            'md_mim_id',
            'gd_hgnc_id',
            'gd_app_sym',
            'gd_app_name',
            'gd_status',
            'gd_prev_sym',
            'gd_prev_name',
            'gd_aliases',
            'gd_date2app_or_res',
            'gd_date_mod',
            'gd_date_sym_change',
            'gd_date_name_change',
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

    public function __construct(Client $guzzleClient)
    {
        $this->client = $guzzleClient;
    }

    public function import($params = null)
    {
        $params = $params ?? $this->defaultParams;

        yield 'fetching data...';
        $customDownload = $this->fetchCustomDownload($params);

        yield 'parsing data...';
        $records = $this->parseCustomResponse($customDownload);
        
        Log::debug('filtered records: '.$records->count());

        yield 'storing '.$records->count().' gene records...';

        $count = 0;
        $records->each(function (HgncRecord $record) use (&$count) {
            $count++;
            $prevSymbols = null;

            $prevSymbols = $this->parsePreviousSymbols($record);
            $aliasSymbols = $this->parseAliasSymbols($record);

            $newAttributes = [
                'gene_symbol'       => $record->approved_symbol,
                'omim_id'           => $record->omim_id ? $record->omim_id : null,
                'ncbi_gene_id'      => (!empty($record->ncbi_gene_id)) ? $record->ncbi_gene_id : null,
                'hgnc_name'         => ($record->status == 'Symbol Withdrawn') 
                                            ? 'symbol withdrawn' 
                                            : $record->approved_name,
                'hgnc_status'       => $record->status,
                'previous_symbols'  => $prevSymbols,
                'alias_symbols'     => $aliasSymbols,
                'date_approved'     => $this->getValueOrNull($record->date_approved),
                'date_modified'     => $this->getValueOrNull($record->date_modified),
                'date_symbol_changed' => $this->getValueOrNull($record->date_symbol_changed),
                'date_name_changed' => $this->getValueOrNull($record->date_name_changed),
            ];

            Gene::updateOrCreate(['hgnc_id' => $record->hgnc_id], $newAttributes);
        });
        yield 'done';
    }

    private function getValueOrNull($attr)
    {
        return ($attr == '') ? null : $attr;
    }
    

    private function parseAliasSymbols($record)
    {
        if ($record->alias_symbols !== '') {
            return array_map(function ($sym) {
                return trim($sym);
            }, explode(',', $record->alias_symbols));
        }

        return null;
    }
    

    private function parsePreviousSymbols($record)
    {
        if ($record->previous_symbols !== '') {
            return array_map(function ($sym) {
                return trim($sym);
            }, explode(',', $record->previous_symbols));
        }
        return null;
    }
    

    public function fetchCustomDownload(array $params): String
    {
        Log::info('Getting data from hgnc.');
        $queryString = $this->queryStringFromParams($params);

        $url = 'www.genenames.org/cgi-bin/download/custom?'.$queryString;
        Log::debug($url);

        $response = $this->client->request('GET', $url);
        $contents = $response->getBody()->getContents();
        Log::debug('Got response with length'.strlen($contents));
        
        return $contents;
    }

    private function getGeneData()
    {
        $genes = Gene::select('hgnc_id', 'date_modified', 'omim_id')->get();

        return [
            $genes->filter(function ($gene) {
                return is_null($gene->omim_id);
            })->pluck('hgnc_id'),
            $genes->pluck('date_modified', 'hgnc_id')->max()
        ];
    }

    private function parseCustomResponse(String $responseString): Collection
    {
        
        [$hgncsWithoutOmimId, $genesLastModified] = $this->getGeneData();

        $lines = explode("\n", $responseString);
        
        $columnNames = null;
        $collection = collect();
        
        Log::debug('lines in download: '.count($lines));
        foreach ($lines as $idx => $line) {
            $cols = explode("\t", $line);

            // Get the column keys.
            if ($idx == 0) {
                $columnNames = array_map(function ($heading) {
                    $heading = preg_replace('/\(.*\)$/', '', $heading);

                    return Str::snake(strtolower($heading));
                }, $cols);
                continue;
            }

            // The last line comes in with single column with empty string value
            // causes an error so break when we hit that case.
            if (count($columnNames) != count($cols)) {
                break;
            }

            $data = array_combine($columnNames, $cols);
            $hgncId = substr($data['hgnc_id'], 5);
            
            if ($hgncId == '16216')

            if (!empty($hgncId) && $hgncsWithoutOmimId->contains($hgncId)) {
                $hgncRecord = new HgncRecord($data);
                $collection->push($hgncRecord);
                continue;
            }

            if ($genesLastModified) {
                if ($data['date_modified'] == '' && Carbon::parse($data['date_approved'])->lt($genesLastModified)) {
                    continue;
                }
                
                if ($data['date_modified'] !== '' && Carbon::parse($data['date_modified'])->lt($genesLastModified)) {
                    continue;
                }
            }

            $hgncRecord = new HgncRecord($data);
            $collection->push($hgncRecord);
        }

        return $collection;
    }

    private function queryStringFromParams($params)
    {
        return preg_replace('/\[\d+\]/', '', urldecode(http_build_query($params)));
    }
}
