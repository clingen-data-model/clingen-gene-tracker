<?php

namespace App\Console\Commands;

use Storage;
use App\Gene;
use App\AppState;
use App\Phenotype;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Response;
use Tests\MocksGuzzleRequests;
use GuzzleHttp\ClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Events\Phenotypes\PhenotypeAddedForGene;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateOmimData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omim:update-data {--file= : Absolute path of genemap2 file to use. Note: remote file will not be fetched}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates OMIM phenotypes from latest genemap2.txt available from OMIM';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Starting Omim genemap2 update...');
        if ($this->option('file')) {
            if (!file_exists($this->option('file'))) {
                $this->error('File not found. '.$this->option('file'). ' does not exist');
                return;
            }
            $testGeneMap = file_get_contents($this->option('file'));
            $httpClient = $this->getGuzzleClient([new Response(200, [], $testGeneMap)]);
            app()->instance(ClientInterface::class, $httpClient);
        }

        $client = app()->make(ClientInterface::class);

        $newDateGenerated = null;
        $lastGeneMapDownload = AppState::findByName('last_genemap_download');
        $archivePath = Storage::path('omim/genemap2.'.Carbon::now()->format('Y-m-d_H:i:s').'.txt.gz');
        $gzfile = gzopen($archivePath, 'wb9');
        try {
            $url = 'https://data.omim.org/downloads/'.config('app.omim_key').'/genemap2.txt';
            $request = $client->get($url, ['stream' => true]);
            $this->info('Retrieved OMIM genemap2 file...');

            $keys = [];
            while (!$request->getBody()->eof()) {
                $line = Utils::readLine($request->getBody());
                gzwrite($gzfile, $line);

                $line = str_replace("\n", ',', $line);

                if ($this->lineIsHeader($line)) {
                    $keys = $this->parseKeys($line);
                    continue;
                }

                if ($this->lineIsDateGenerated($line)) {
                    $newDateGenerated = $this->getGeneratedDate($line);
                    if (!is_null($lastGeneMapDownload->value) && $lastGeneMapDownload->value->gte($newDateGenerated)) {
                        // Close and remove the archive since we're not using the file.
                        gzclose($gzfile);
                        unlink($archivePath);
                        return;
                    }
                }
                
                if ($this->lineIsGarbage($line)) {
                    continue;
                }

                $data = $this->linkValuesToKeys($line, $keys);
                if (count($data) == 0) {
                    continue;
                }

                if (!$this->recordHasGeneSymbol($data)) {
                    continue;
                }
                $gene = $this->getGene($data);

                if (!$gene) {
                    Log::warning('Gene with approved_symbol '.$this->getGeneSymbol($data).' and omim id '.$data['mim_number'].' not found.');
                    continue;
                }

                $phenotypes = $this->parsePhenotypes($data['phenotypes']);
                if (count($phenotypes) == 0) {
                    continue;
                }


                $phenotypes = collect($phenotypes)
                                ->map(function ($pheno) use ($gene) {
                                    try {
                                        // A mim_number can refer to many differently named phenotypes
                                        // If this is the case try to get the phenotype record by mim_number 
                                        // and name to prevent constraint failures.
                                        $phenotype = null;
                                        try {
                                            $phenotype = Phenotype::findSoleByMimNumber($pheno['mim_number']);
                                        } catch (MultipleRecordsFoundException $e) {
                                            $phenotype = Phenotype::mimNumber($pheno['mim_number'])
                                                            ->where('name', $pheno['name'])
                                                            ->first();
                                        } catch (ModelNotFoundException $e) {
                                        }

                                        if ($phenotype) {
                                            $phenotype->update([
                                                'name' => trim($pheno['name']),
                                                'moi' => $pheno['moi']
                                            ]);
                                            return $phenotype;
                                        }
                                        
                                        $phenotype = Phenotype::updateOrCreate(
                                            [
                                                'mim_number' => $pheno['mim_number'],
                                                'name' => trim($pheno['name']),
                                            ],
                                            [
                                                'moi' => $pheno['moi']
                                            ]
                                        );
                                        event(new PhenotypeAddedForGene($phenotype, $gene));

                                        return $phenotype;
                                    } catch (\Throwable $th) {
                                        Log::warning($th->getMessage());
                                        throw $th;
                                        return null;
                                    }
                                });
                                
                $gene->phenotypes()->syncWithoutDetaching($phenotypes->pluck('id')->filter());
            }
            $lastGeneMapDownload->update(['value' => $newDateGenerated]);
            gzclose($gzfile);
        } catch (ClientException $e) {
            gzclose($gzfile);
            unlink($archivePath);
            $this->error($e->getMessage());
            \Log::error($e->getMessage());
        }
        Log::info('Finished Omim genemap2 update.');

        
    }

    private function parseKeys($line)
    {
        $keys = explode("\t", $line);
        $keys = array_map(function ($key) {
            return Str::snake(strtolower(str_replace('# ', '', trim($key))));
        }, $keys);
        return $keys;
    }
    
    private function lineIsHeader($line)
    {
        return substr($line, 0, 35) == '# Chromosome	Genomic Position Start';
    }

    private function lineIsGarbage($line)
    {
        return substr($line, 0, 1) == '#' && substr($line, 0, 35) != '# Chromosome	Genomic Position Start';
    }

    private function lineIsDateGenerated($line)
    {
        return substr($line, 0, 13) == '# Generated: ';
    }

    private function getGeneratedDate($line)
    {
        return Carbon::parse(substr($line, 13, 10));
    }
    

    private function linkValuesToKeys($line, $keys)
    {
        $values = explode("\t", $line);
        if ($values[0] == '') {
            return [];
        }
        return array_combine($keys, array_pad($values, count($keys), null));
    }

    private function getGene($data)
    {
        if (!$this->recordHasGeneSymbol($data)) {
            return null;
        }

        // First try to get the gene by the mim_number
        $gene = Gene::findByOmimId($data['mim_number']);
        if (!$gene) {
            // Next try to find it by the hgnc symbol
            $gene = Gene::findBySymbol($this->getGeneSymbol($data));
        }
        return $gene;
    }

    private function recordHasGeneSymbol($data)
    {
        if (!$this->getGeneSymbol($data)) {
            return false;
        }
        return true;
    }

    private function getGeneSymbol($data)
    {
        if (isset($data['approved_symbol'])) {
            return $data['approved_symbol'];
        }

        if (isset($data['approved_gene_symbol'])) {
            return $data['approved_gene_symbol'];
        }
        \Log::warning("OMIM record does not have approved_symbol", $data);
        return null;
    }
    
    

    private function parsePhenotypes($string)
    {
        if (empty($string)) {
            return [];
        }
        $parts = explode(';', $string);
        $phenotypes = [];
        foreach ($parts as $part) {
            $matches = [];
            preg_match('/^(.*), (\d{6}) \(\d\)(, (.*))?$/', $part, $matches);
            if (count($matches) < 2) {
                Log::debug('Phenotype string "'.$part.'" without mim number found in phenotype string "'.$string.'"', $matches);
                continue;
            }
            $phenotypes[] = [
                'name' => trim($matches[1]),
                'mim_number' => $matches[2],
                'moi' => isset($matches[4]) ? trim($matches[4]) : null
            ];
        }

        return $phenotypes;
    }
}
