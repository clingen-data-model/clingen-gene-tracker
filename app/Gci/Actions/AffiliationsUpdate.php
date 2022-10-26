<?php
namespace App\Gci\Actions;

use App\Affiliation;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;

class AffiliationsUpdate
{
    use AsCommand;

    public string $commandSignature = 'gci:affiliations-update {--storeJson}';

    public function handle(Client $client, Command $command)
    {
        $response = $client->get(
            uri: 'https://6wbpqalizj.execute-api.us-west-2.amazonaws.com/prod/affiliations?target=api',
            options: [
                'headers'=>[
                    'x-api-key' => config('app.gci_api_key')
                ]
            ]
        );

        $affiliationData = json_decode($response->getBody()->getContents());

        if ($command->option('storeJson')) {
            file_put_contents('gci_affiliations.json', json_encode($affiliationData, JSON_PRETTY_PRINT));
        }

        collect($affiliationData)
            ->each(function ($aff) {
                $name = $aff->affiliation_fullname .' Parent';
                $parent = Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $aff->affiliation_id], [
                    'name' => $name,
                    'affiliation_type_id' => 1,
                    'parent_id' => null
                ]);
                if (isset($aff->subgroups)) {
                    foreach ((array)$aff->subgroups as $type => $sub) {
                        Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $sub->id],[
                            'clingen_id' => $sub->id,
                            'name' => $sub->fullname,
                            'affiliation_type_id' => ($type == 'vcep') ? 4 : 3,
                            'parent_id' =>  $parent->id
                        ]);
                    }
                }
            });
        \Log::info('Affiliations synced from GCI/VCI api.');
    }


    
}
