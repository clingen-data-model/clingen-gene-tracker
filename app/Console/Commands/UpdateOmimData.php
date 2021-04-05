<?php

namespace App\Console\Commands;

use App\Gene;
use App\Phenotype;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;
use GuzzleHttp\ClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class UpdateOmimData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omim:update-data';

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
        $client = app()->make(ClientInterface::class);
        try {
            $request = $client->get('https://data.omim.org/downloads/6tNWNo1sQ-O1xzRqwiP-KA/genemap2.txt', ['stream' => true]);
            
            $keys = [];
            $pCount = 0;
            while (!$request->getBody()->eof()) {
                $line = Utils::readLine($request->getBody());
                $line = str_replace("\n", ',', $line);
                if (substr($line, 0, 35) == '# Chromosome	Genomic Position Start') {
                    $keys = explode("\t", $line);
                    $keys = array_map(function ($key) { return Str::snake(strtolower(str_replace('# ', '', trim($key))));}, $keys);
                }
                if(substr($line, 0, 1) == '#') {
                    continue;
                }

                $values = explode("\t", $line);
                if ($values[0] == '') {
                    continue;
                }
                $data = array_combine($keys, array_pad($values, count($keys), null));
                
                $geneSymbol = $data['approved_symbol'];
                $geneMimNumber = $data['mim_number'];
                $phenotypes = $this->parsePhenotypes($data['phenotypes']);
                $pCount += count($phenotypes);

                $gene = Gene::findByOmimId($geneMimNumber);
                if (!$gene) {
                    Log::warning('Gene with omim id '.$geneMimNumber.' not found.');
                }

                $phenotypes = collect($phenotypes)->map(function ($pheno) {
                    return Phenotype::updateOrCreate(
                        ['mim_number' => $pheno['mim_number']],
                        [
                            'name' => $pheno['name']
                        ]
                    );
                });
                $gene->phenotypes()->syncWithoutDetaching($phenotypes->pluck('id'));
            }

        } catch (ClientException $e) {
            $this->error($e->getMessage());
        }

    }

    private function parsePhenotypes($string)
    {
        $parts = explode(';', $string);
        $phenotypes = [];
        foreach ($parts as $part) {
            $matches = [];
            preg_match('/^(.*), (\d{6}) \(\d\)(, (.*))?$/', $part, $matches);
            if (count($matches) < 2) {
                dd($part);
            }
            $phenotypes[] = [
                'name' => $matches[1],
                'mim_number' => $matches[2],
                'moi' => isset($matches[4]) ? $matches[4] : null
            ];
        }

        return $phenotypes;
    }
    
}
