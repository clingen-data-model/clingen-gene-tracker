<?php

namespace App\Http\Controllers\Api;

use App\Actions\ApiClientCreate;
use App\Actions\ApiClientCreateToken;
use App\ApiClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiClientRequest;
use App\Http\Requests\ApiClientTokenRequest;
use Illuminate\Http\Request;

class ApiClientAdminController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $clients = ApiClient::query()->withCount('tokens')->orderBy('name')->paginate($perPage);
        $clients->getCollection()->transform(fn (ApiClient $client) => $this->serialize($client));
        return $clients;
    }

    public function show(ApiClient $apiClient)
    {
        return $this->serialize($apiClient->load('tokens'));
    }

    public function store(ApiClientRequest $request, ApiClientCreate $createClient)
    {
        $client = $createClient->handle($request->validated('name'), $request->validated('contact_email'));
        return response()->json($this->serialize($client), 201);
    }

    public function update(ApiClientRequest $request, ApiClient $apiClient)
    {
        $apiClient->update($request->only(['name', 'contact_email']));
        return $this->serialize($apiClient->fresh());
    }

    public function createToken(ApiClientTokenRequest $request, ApiClient $apiClient, ApiClientCreateToken $createToken)
    {
        $token = $createToken->handle($apiClient, $request->validated('name'));
        return response()->json([
            'plain_text_token' => $token->plainTextToken,
            'token' => $this->tokenMetadata($token->accessToken),
        ], 201);
    }

    public function destroyToken(ApiClient $apiClient, int $token)
    {
        $accessToken = $apiClient->tokens()->whereKey($token)->firstOrFail();
        $accessToken->delete();
        return response()->noContent();
    }

    private function serialize(ApiClient $client): array
    {
        $tokens = $client->relationLoaded('tokens')
            ? $client->tokens->sortByDesc('created_at')->values()->map(fn ($token) => $this->tokenMetadata($token))->all()
            : [];

        return [
            'id' => $client->id,
            'uuid' => $client->uuid,
            'name' => $client->name,
            'contact_email' => $client->contact_email,
            'tokens_count' => $client->tokens_count ?? count($tokens),
            'last_token_activity' => $client->relationLoaded('tokens')
                ? optional($client->tokens->sortByDesc(fn ($token) => $token->last_used_at ?? $token->created_at)->first())->last_used_at
                : null,
            'tokens' => $tokens,
            'created_at' => $client->created_at,
            'updated_at' => $client->updated_at,
        ];
    }

    private function tokenMetadata($token): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'created_at' => $token->created_at,
            'last_used_at' => $token->last_used_at,
            'expires_at' => $token->expires_at,
        ];
    }
}
