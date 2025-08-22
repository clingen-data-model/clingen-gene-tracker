<?php
namespace App\Gci\Actions;

use App\Affiliation;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;

class AffiliationsUpdate
{
    use AsCommand;

    public string $commandSignature = 'affiliations:update-data {--storeJson}';
    private array $affiliationTypes;

    public function __construct()
    {
        $this->affiliationTypes = config('affiliations.types');
    }

    protected function fetchData(string $uri, Client $client): array
    {
        $response = $client->get(
            uri: $uri,
            options: [
                'headers'=>[
                    'x-api-key' => config('app.affiliations_api_key')
                ]
            ]
        );

        return json_decode($response->getBody()->getContents());
    }

    public function updateAffiliationData(array $affiliationData): void
    {
        collect($affiliationData)
            ->each(function ($aff) {
                $name = $aff->affiliation_fullname .' Parent ' . $aff->affiliation_id;
                $parent = Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $aff->affiliation_id], [
                    'name' => $name,
                    'affiliation_type_id' => $this->affiliationTypes['working-group'],
                    'parent_id' => null
                ]);
                if (isset($aff->subgroups)) {
                    foreach ((array)$aff->subgroups as $type => $sub) {
                        Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $sub->id],[
                            'clingen_id' => $sub->id,
                            'name' => $sub->fullname,
                            'affiliation_type_id' => $this->affiliationTypes[$type],
                            'parent_id' =>  $parent->id
                        ]);
                    }
                }
            });
        // Process the affiliation data as needed
    }

    public function handle(Client $client, Command $command)
    {
        $affiliationData = $this->fetchData(config('app.affiliations_api_url'), $client);

        if ($command->option('storeJson')) {
            file_put_contents('affiliations.json', json_encode($affiliationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $this->updateAffiliationData($affiliationData);
        \Log::info('Affiliations synced from GCI/VCI api.');
    }


    
}
