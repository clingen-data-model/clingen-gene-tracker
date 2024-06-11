<?php

namespace App\Console\Commands\Mondo;

use App\Disease;
use App\AppState;
use App\Mondo\OboParser;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\ClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateMondoData extends Command
{
    const MONDO_OBO_URI = 'http://purl.obolibrary.org/obo/mondo.obo';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mondo:update-data {--file= : Path to mondo.obo (will prevent fresh download)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates local mondo data.';

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
    public function handle(ClientInterface $guzzleClient, OboParser $parser)
    {
        Log::info('Starting to update MonDO data.');
        $tmpFilePath = $this->option('file');
        if (!$this->option('file')) {
            $this->info('downloading latest obo...');
            $tmpFilePath = $this->downloadOboFile($guzzleClient);
            $this->info('donwload complete.');
        }
        
        $this->info('Parsing obo file and updating diseases.');
        $parser->setOboPath($tmpFilePath);
        
        while ($term = $parser->getNextTerm()) {
            if (str_starts_with($term['mondo_id'], 'MONDO:')) {
                Disease::updateOrCreate(['mondo_id' => $term['mondo_id']], $term);
            }
        }

        $lastMondoUpdate = AppState::findByName('last_mondo_update');
        if (is_null($lastMondoUpdate->value) || $lastMondoUpdate->value->lte(Carbon::parse($parser->getVersionDate()))) {
            $this->info('Updating last_mondo_update state variable to '.$parser->getVersionDate());
            $lastMondoUpdate->set($parser->getVersionDate())
                            ->save();
        }

        $this->info('Deleting mondo temp file');
        unlink($tmpFilePath);
        Log::info('Finished updating MonDO data.');
    }

    private function downloadOboFile(ClientInterface $guzzleClient)
    {
        $response = $guzzleClient->get(static::MONDO_OBO_URI, ['stream' => true]);
        $tmpFilePath = storage_path('app/mondo_'.uniqid().'.obo');
        $handle = fopen($tmpFilePath, 'w');

        $lastMondoUpdate = AppState::findByName('last_mondo_update');
        while (!$response->getBody()->eof()) {
            $line = Utils::readLine($response->getBody());
            fwrite($handle, $line);

            /**
             * TODO: this doesn't belong here but I don't want to:
             * 1. Start an extra http connections to find out if I want to download the whole thing.
             * 2. Download the whole thing and then check the date.
             */
            if (!$this->dataIsNew($line, $lastMondoUpdate)) {
                $this->info('Mondo data is not new. Stop download.');
                break;
            }
        }
        fclose($handle);
        return $tmpFilePath;
    }

    private function dataIsNew($line, $lastMondoUpdate)
    {
        if (substr($line, 0, 13) == 'data-version:') {
            $versionDate = Carbon::parse(substr($line, 23, 10));
            if (!is_null($lastMondoUpdate->value) && $lastMondoUpdate->value->gte(Carbon::parse($versionDate))) {
                return false;
            }
        }
        return true;
    }
}
