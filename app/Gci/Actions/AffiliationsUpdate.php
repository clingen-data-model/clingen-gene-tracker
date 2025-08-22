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

    public function handle(Client $client, Command $command)
    {
        $response = $client->get(
            uri: config('app.affiliations_api_url'),
            options: [
                'headers'=>[
                    'x-api-key' => config('app.affiliations_api_key')
                ]
            ]
        );

        $affiliationData = json_decode($response->getBody()->getContents());
        $affiliationTypes = config('affiliations.types');

        if ($command->option('storeJson')) {
            file_put_contents('affiliations.json', json_encode($affiliationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        collect($affiliationData)
            ->each(function ($aff) use ($affiliationTypes) {
                $name = $aff->affiliation_fullname .' Parent';
                $parent = Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $aff->affiliation_id], [
                    'name' => $name,
                    'affiliation_type_id' => $affiliationTypes['working-group'],
                    'parent_id' => null
                ]);
                if (isset($aff->subgroups)) {
                    foreach ((array)$aff->subgroups as $type => $sub) {
                        Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $sub->id],[
                            'clingen_id' => $sub->id,
                            'name' => $sub->fullname,
                            'affiliation_type_id' => $affiliationTypes[$type],
                            'parent_id' =>  $parent->id
                        ]);
                    }
                }
            });
        \Log::info('Affiliations synced from GCI/VCI api.');
    }


    
}
