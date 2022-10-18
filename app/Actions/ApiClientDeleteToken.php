<?php

namespace App\Actions;

use Laravel\Sanctum\Sanctum;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ApiClientDeleteToken
{
    use AsController;
    
    public function handle(int $tokenId): void
    {
        $tokenModel = Sanctum::$personalAccessTokenModel;
        $token = $tokenModel::findOrFail($tokenId);
        $token->delete();
    }
    
    public function asController(ActionRequest $request, $tokenId)
    {
        $this->handle($tokenId);

        return response('api token deleted', 200);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasAnyRole(['programmer', 'admin']);
    }
}

