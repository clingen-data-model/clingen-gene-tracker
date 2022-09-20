<?php

namespace App\Actions;

use App\ApiClient;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;

class ApiClientCreateToken
{
    use AsCommand;

    public $commandSignature = 'api-client:create-token {id : ID of the ApiClient.} {tokenName : name for token.}';

    public function handle(ApiClient $apiClient, string $tokenName)
    {
        $token = $apiClient->createToken($tokenName);

        return $token;
    }

    public function asCommand(Command $command)
    {
        $apiClient = ApiClient::findOrFail($command->argument('id'));

        $token = $this->handle($apiClient, $command->argument('tokenName'));

        $command->info('New token created for ApiClient '.$apiClient->name.': '.$token->plainTextToken);
    }
    
}
