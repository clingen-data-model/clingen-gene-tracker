<?php
namespace App\Gci\Actions;

use App\Affiliation;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
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

        if ($command->option('storeJson')) {
            file_put_contents('affiliations.json', json_encode($affiliationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        collect($affiliationData)
        ->each(function ($aff) {
            $name = $aff->affiliation_fullname .' Parent';            
            $this->handleDuplicateName($name, $aff->affiliation_id);
            $parent = Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $aff->affiliation_id], [
                    'name' => $name,
                    'affiliation_type_id' => 1,
                    'parent_id' => null
                ]);
            if (isset($aff->subgroups)) {
                foreach ((array)$aff->subgroups as $type => $sub) {
                    $subName = $sub->fullname;                    
                    $this->handleDuplicateName($subName, $sub->id);
                    Affiliation::withTrashed()->updateOrCreate(['clingen_id' => $sub->id],[
                            'name' => $subName,
                            'affiliation_type_id' => ($type == 'vcep') ? 4 : 3,
                            'parent_id' => $parent->id
                        ]);
                }
            }
        });
        \Log::info('Affiliations synced from GCI/VCI api.');
    }

    /**
     * Check for duplicate name and soft delete existing record if needed.
     */
    protected function handleDuplicateName(string $name, string $currentClingenId): void
    {
        $existing = Affiliation::withTrashed()
            ->where('name', $name)
            ->where('clingen_id', '!=', $currentClingenId)
            ->first();

        if ($existing) {
            // Soft delete and rename existing record
            $newName = $name . ' (Soft Delete)';

            // Ensure the renamed value doesn't exceed column length (e.g., 255)
            $existing->name = Str::limit($newName, 255);
            $existing->save();

            if (!$existing->trashed()) {
                $existing->delete();
            }

            \Log::info("Duplicate name found. Soft deleted and renamed affiliation ID: {$existing->id}");
        }
    }
}
