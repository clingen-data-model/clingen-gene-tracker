<?php

namespace App\Hgnc;

use App\Gene;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Hgnc\HgncRecord;
use Illuminate\Support\Str;
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
        $path = $this->fetchCustomDownload($params);

        yield 'storing gene records...';
        $count = 0;
        try {
            foreach ($this->parseCustomResponse($path) as $record) {
                $this->storeRecord($record);
                $count++;
            }
        } finally {
            unlink($path);
        }

        Log::debug('stored records: '.$count);
        yield 'stored '.$count.' gene records';
        yield 'done';
    }

    private function storeRecord(HgncRecord $record)
    {
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
    

    /**
     * Downloads to a temp file, whose path is returned; the caller must delete it.
     */
    public function fetchCustomDownload(array $params): string
    {
        Log::info('Getting data from hgnc.');
        $queryString = $this->queryStringFromParams($params);

        $url = 'http://www.genenames.org/cgi-bin/download/custom?'.$queryString;
        Log::debug($url);

        $path = tempnam(sys_get_temp_dir(), 'hgnc');
        try {
            $this->client->request('GET', $url, ['sink' => $path]);
        } catch (\Throwable $e) {
            unlink($path);
            throw $e;
        }
        Log::debug('Got response with length '.filesize($path));

        return $path;
    }

    private function getGeneData()
    {
        $lastModified = Gene::max('date_modified');

        return [
            Gene::whereNull('omim_id')->toBase()->pluck('hgnc_id')->flip()->all(),
            $lastModified ? Carbon::parse($lastModified) : null,
        ];
    }

    private function parseCustomResponse(string $path): \Generator
    {
        // Snapshot before the caller starts updating genes.
        [$hgncsWithoutOmimId, $genesLastModified] = $this->getGeneData();

        $handle = fopen($path, 'r');
        try {
            $columnNames = null;
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                $cols = explode("\t", $line);

                // Get the column keys.
                if ($columnNames === null) {
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

                if (isset($hgncsWithoutOmimId[$hgncId])) {
                    yield new HgncRecord($data);
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

                yield new HgncRecord($data);
            }
        } finally {
            fclose($handle);
        }
    }

    private function queryStringFromParams($params)
    {
        return preg_replace('/\[\d+\]/', '', urldecode(http_build_query($params)));
    }
}
