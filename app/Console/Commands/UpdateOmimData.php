<?php

namespace App\Console\Commands;

use Storage;
use App\Gene;
use App\AppState;
use App\Phenotype;
use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Events\Phenotypes\PhenotypeAddedForGene;

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
     * @return int
     */
    public function handle()
    {
        Log::info('Starting Omim genemap2 update...');

        $lastGeneMapDownload = AppState::findByName('last_genemap_download');
        $timestamp = Carbon::now()->format('Ymd_His_v');
        $archivePath = Storage::path("omim/genemap2.{$timestamp}.txt.gz");
        $downloadPath = null;

        try {
            if ($this->option('file')) {
                $sourcePath = $this->option('file');
                if (!file_exists($sourcePath)) {
                    $this->error('File not found. '.$sourcePath.' does not exist');
                    return Command::FAILURE;
                }
            } else {
                $downloadPath = Storage::path("omim/genemap2.{$timestamp}.download.txt");
                $url = 'https://data.omim.org/downloads/'.config('app.omim_key').'/genemap2.txt';
                $client = app()->make(ClientInterface::class);

                $this->info('Downloading OMIM genemap2 file...');

                $client->get($url, ['sink' => $downloadPath]);
                $sourcePath = $downloadPath;
                $this->info('Retrieved OMIM genemap2 file.');
            }

            // Validate the complete local file before touching the database.
            $inspection = $this->inspectOmimFile($sourcePath);
            $newDateGenerated = $inspection['date_generated'];

            if (!$inspection['header_reached'] || is_null($newDateGenerated) || !$inspection['footer_reached']) 
            {
                $this->archiveOmimFile($sourcePath, $archivePath);

                $message = 'OMIM genemap2 file appears invalid or truncated '
                    .'(header='.($inspection['header_reached'] ? 'yes' : 'no')
                    .', generated_date='.(!is_null($newDateGenerated) ? 'yes' : 'no')
                    .', footer='.($inspection['footer_reached'] ? 'yes' : 'no')
                    .", lines={$inspection['lines']}); "
                    .'skipping phenotype processing and last-download timestamp.';

                $this->error($message);
                Log::error($message);
                return Command::FAILURE;
            }

            if (!is_null($lastGeneMapDownload->value) && $lastGeneMapDownload->value->gte($newDateGenerated)) {
                return Command::SUCCESS;
            }

            // Archive the complete validated file. Do we want to keep archiving the file?
            $this->archiveOmimFile($sourcePath, $archivePath);

            // Only now start making database changes.
            $seenPhenotypeIds = $this->processOmimFile($sourcePath);
            $seenPhenotypeIds = array_values(array_unique($seenPhenotypeIds));

            if (count($seenPhenotypeIds) > 0) {
                Phenotype::whereNull('deleted_at')
                    ->whereNotIn('id', $seenPhenotypeIds)
                    ->whereNull('label_obsolete_at')
                    ->update([
                        'label_obsolete_at' => now(),
                    ]);
            }

            $lastGeneMapDownload->update([
                'value' => $newDateGenerated,
            ]);
        } catch (GuzzleException|\RuntimeException|\ValueError $e) {
            $this->error($e->getMessage());
            Log::error($e->getMessage());
            return Command::FAILURE;
        } finally {
            // The plain downloaded file is temporary.
            if ($downloadPath && file_exists($downloadPath)) {
                unlink($downloadPath);
            }
        }

        Log::info('Finished Omim genemap2 update.');

        return Command::SUCCESS;
    }

    private function inspectOmimFile($filePath)
    {
        $resource = $this->openOmimFile($filePath);

        $dateGenerated = null;
        $headerReached = false;
        $footerReached = false;
        $processedLines = 0;

        try {
            while (($line = fgets($resource)) !== false) {
                $processedLines++;

                if ($this->lineIsHeader($line)) {
                    $headerReached = true;
                }

                if ($this->lineIsDateGenerated($line)) {
                    $dateGenerated = $this->getGeneratedDate($line);
                }

                if ($this->lineIsFooter($line)) {
                    $footerReached = true;
                }
            }
        } finally {
            fclose($resource);
        }

        return [
            'date_generated' => $dateGenerated,
            'header_reached' => $headerReached,
            'footer_reached' => $footerReached,
            'lines' => $processedLines,
        ];
    }

    private function openOmimFile($filePath)
    {
        $path = Str::endsWith(strtolower($filePath), '.gz') ? 'compress.zlib://'.$filePath : $filePath;
        $resource = fopen($path, 'rb');
        if ($resource === false) {
            throw new \RuntimeException('Could not open OMIM file '.$filePath);
        }
        return $resource;
    }

    private function processOmimFile($filePath)
    {
        $resource = $this->openOmimFile($filePath);

        $keys = [];
        $seenPhenotypeIds = [];
        $processedLines = 0;

        try {
            while (($line = fgets($resource)) !== false) {
                $processedLines++;

                if ($processedLines % 1000 === 0) {
                    $this->info("Processed {$processedLines} lines...");
                }

                $line = str_replace("\n", ',', $line);

                if ($this->lineIsHeader($line)) {
                    $keys = $this->parseKeys($line);
                    continue;
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
                    Log::warning('Gene with approved_symbol ' . $this->getGeneSymbol($data) . ' and omim id '.$data['mim_number'] . ' not found.');
                    continue;
                }
                $phenotypes = $this->parsePhenotypes($data['phenotypes']);

                if (count($phenotypes) == 0) { continue; }

                $phenotypes = collect($phenotypes)->map(function ($pheno) use ($gene, &$seenPhenotypeIds) {
                    try {
                        $phenotype = Phenotype::updateOrCreate(
                            [
                                'mim_number' => $pheno['mim_number'],
                                'name' => trim($pheno['name']),
                            ],
                            [
                                'moi' => $pheno['moi'],
                                'label_obsolete_at' => null,
                            ]
                        );

                        if ($phenotype->wasRecentlyCreated) {
                            event(new PhenotypeAddedForGene($phenotype, $gene));
                        }

                        $seenPhenotypeIds[] = $phenotype->id;

                        return $phenotype;
                    } catch (\Throwable $th) {
                        Log::warning($th->getMessage());
                        return null;
                    }
                });

                $gene->phenotypes()->syncWithoutDetaching($phenotypes->pluck('id')->filter());
            }
        } finally {
            fclose($resource);
        }

        return $seenPhenotypeIds;
    }

    private function archiveOmimFile($sourcePath, $archivePath)
    {
        $source = $this->openOmimFile($sourcePath);
        $archive = gzopen($archivePath, 'wb9');

        if ($archive === false) {
            fclose($source);
            throw new \RuntimeException('Could not create OMIM archive '.$archivePath);
        }

        $completed = false;

        try {
            while (!feof($source)) {
                $chunk = fread($source, 1024 * 1024);

                if ($chunk === false) {
                    throw new \RuntimeException('Could not read OMIM source file.');
                }

                if ($chunk !== '' && gzwrite($archive, $chunk) === false) {
                    throw new \RuntimeException('Could not write OMIM archive.');
                }
            }

            $completed = true;
        } finally {
            fclose($source);
            gzclose($archive);

            // Remove only an archive that failed while being created.
            if (!$completed && file_exists($archivePath)) {
                unlink($archivePath);
            }
        }
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
        return substr($line, 0, 1) == '#'
            && substr($line, 0, 35) != '# Chromosome	Genomic Position Start';
    }

    private function lineIsFooter($line)
    {
        return Str::startsWith($line, '# Genomic Coordinates');
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

        if (count($keys) === 0) {
            throw new \ValueError(
                'OMIM genemap2 header keys were not parsed before data rows were encountered.'
            );
        }

        return array_combine($keys, array_pad($values, count($keys), null));
    }

    private function getGene($data)
    {
        if (!$this->recordHasGeneSymbol($data)) {
            return null;
        }

        // First try to get the gene by the mim_number.
        $gene = Gene::findByOmimId($data['mim_number']);

        if (!$gene) {
            // Next try to find it by the hgnc symbol.
            $gene = Gene::findBySymbol($this->getGeneSymbol($data));
        }

        return $gene;
    }

    private function recordHasGeneSymbol($data)
    {
        return (bool) $this->getGeneSymbol($data);
    }

    private function getGeneSymbol($data)
    {
        if (isset($data['approved_symbol'])) {
            return $data['approved_symbol'];
        }

        if (isset($data['approved_gene_symbol'])) {
            return $data['approved_gene_symbol'];
        }

        Log::warning('OMIM record does not have approved_symbol', $data);

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
                'moi' => isset($matches[4]) ? trim($matches[4]) : null,
            ];
        }

        return $phenotypes;
    }
}
