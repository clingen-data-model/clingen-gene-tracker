<?php

namespace App\Console\Commands;

use App\Gene;
use App\AppState;
use App\Phenotype;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Events\Phenotypes\PhenotypeAddedForGene;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Tests\MocksGuzzleRequests;

class UpdateOmimData extends Command
{
    use MocksGuzzleRequests; // to make a file act like a Guzzle response

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
        $this->info('Starting Omim genemap2 update...');
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
            $url = 'https://data.omim.org/downloads/'.config('app.omim_key').'/genemap2.txt';
            $request = $client->get($url, ['stream' => true]);
            $this->info('Retrieved file...');

            $keys = [];
            while (!$request->getBody()->eof()) {
                $line = Utils::readLine($request->getBody());
                $line = str_replace("\n", '', $line);

                if (str_starts_with($line, '# Chromosome	Genomic Position Start')) {
                    $keys = $this->parseKeys($line);
                    $this->info('Parsed keys...');
                    continue;
                }

                if (str_starts_with($line, '# Generated: ')) {
                    $newDateGenerated = Carbon::parse(substr($line, 13, 10));
                    if (!is_null($lastGeneMapDownload->value) && $lastGeneMapDownload->value->gte($newDateGenerated)) {
                        return;
                    }
                }
                
                if ($line == '' || $line[0] == '#') {
                    // ignore all other comment lines and empty lines
                    continue;
                }

                $data = array_combine($keys, array_pad(explode("\t", $line), count($keys), null));

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
                                        $phenotype = Phenotype::mimNumber($pheno['mim_number'])
                                                        ->where('name', $pheno['name'])
                                                        ->first();

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
                /*
                // This would require a schema change (adding 'historical' to omim_status enum in phenotypes table)
                $old_pheno_ids = $gene->phenotypes()->whereNotIn('phenotype_id', $phenotypes->pluck('id')->filter())->pluck('phenotype_id');
                if ($old_pheno_ids->count() > 0) {
                    Phenotype::whereIn('id', $old_pheno_ids)->update(['omim_status' => 'historical']);
                    $this->info('Marked '.$old_pheno_ids->count().' phenotypes for gene '.$gene->gene_symbol.' as historical.');
                }
                */
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

    private function getGene($data)
    {
        if (!$this->recordHasGeneSymbol($data)) {
            return null;
        }

        // First try to get the gene by the mim_number
        $gene = Gene::findByOmimId($data['mim_number']);
        if (!$gene) {
            $this->info('Gene not found by mim_number '.$data['mim_number'].'. Trying symbol.');
            // Next try to find it by the hgnc symbol
            $gene = Gene::findBySymbol($this->getGeneSymbol($data));
        }
        return $gene;
    }

    private function recordHasGeneSymbol($data)
    {
        return $this->getGeneSymbol($data) != null;
    }

    private function getGeneSymbol($data)
    {
        return $data['approved_symbol'] ?? $data['approved_gene_symbol'] ?? null;
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
