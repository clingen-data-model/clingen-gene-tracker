<?php

namespace App\Console\Commands;

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

class UpdateOmimData extends Command
{
    use MocksGuzzleRequests;

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
        try {
            $request = $client->get('https://data.omim.org/downloads/6tNWNo1sQ-O1xzRqwiP-KA/genemap2.txt', ['stream' => true]);
            $this->info('Retrieved file...');

            $keys = [];
            while (!$request->getBody()->eof()) {
                $line = Utils::readLine($request->getBody());
                $line = str_replace("\n", ',', $line);

                if ($this->lineIsHeader($line)) {
                    $keys = $this->parseKeys($line);
                    continue;
                }
               
                if ($this->lineIsDateGenerated($line)) {
                    $newDateGenerated = $this->getGeneratedDate($line);
                    if (!is_null($lastGeneMapDownload->value) && $lastGeneMapDownload->value->gte($newDateGenerated)) {
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
                    Log::warning('Gene with approved_symbol '.$data['approved_symbol'].' and omim id '.$data['mim_number'].' not found.');
                    continue;
                }

                $phenotypes = $this->parsePhenotypes($data['phenotypes']);
                if (count($phenotypes) == 0) {
                    continue;
                }

                $phenotypes = collect($phenotypes)
                                ->map(function ($pheno) {
                                    try {
                                        return Phenotype::updateOrCreate(
                                            ['mim_number' => $pheno['mim_number']],
                                            [
                                                'name' => trim($pheno['name']),
                                                'moi' => $pheno['moi']
                                            ]
                                        );
                                    } catch (\Throwable $th) {
                                        Log::warning($th->getMessage());
                                        return null;
                                    }
                                });
                                
                $gene->phenotypes()->syncWithoutDetaching($phenotypes->pluck('id')->filter());
            }
            $lastGeneMapDownload->update(['value' => $newDateGenerated]);
        } catch (ClientException $e) {
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

        return Gene::findByOmimId($data['mim_number']);
    }

    private function recordHasGeneSymbol($data)
    {
        $geneSymbol = $data['approved_symbol'];
        if (!$geneSymbol) {
            return false;
        }
        return true;
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
                Log::debug('Phenotype string '.$string.' without mim number found', $matches);
                continue;
            }
            $phenotypes[] = [
                'name' => $matches[1],
                'mim_number' => $matches[2],
                'moi' => isset($matches[4]) ? trim($matches[4]) : null
            ];
        }

        return $phenotypes;
    }
}
