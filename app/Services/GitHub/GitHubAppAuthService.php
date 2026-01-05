<?php

namespace App\Services\GitHub;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GitHubAppAuthService
{
    public function getInstallationToken(): string
    {
        $cacheKey = 'github_mondo_installation_token';

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $appId = (int) config('github_mondo.app_id');
        $installationId = (int) config('github_mondo.installation_id');
        $privateKeyPath = (string) config('github_mondo.private_key_path');

        if (!$appId || !$installationId || !$privateKeyPath) {
            throw new \RuntimeException('Missing GitHub MONDO config (app_id, installation_id, private_key_path).');
        }

        $fullPath = base_path($privateKeyPath);
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("GitHub private key file not found at: {$fullPath}");
        }

        $privateKey = file_get_contents($fullPath);
        if (!is_string($privateKey) || trim($privateKey) === '') {
            throw new \RuntimeException("GitHub private key file is empty/unreadable: {$fullPath}");
        }

        // 1) Create a short-lived JWT for the GitHub App
        $now = time();
        $payload = [
            'iat' => $now - 60,
            'exp' => $now + (9 * 60),
            'iss' => $appId,
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        // 2) Exchange JWT for installation access token
        $resp = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'GeneTracker',
            ])
            ->post("https://api.github.com/app/installations/{$installationId}/access_tokens");

        if (!$resp->successful()) {
            throw new \RuntimeException('Failed to get installation token: '.$resp->status().' '.$resp->body());
        }

        $token = $resp->json('token');
        $expiresAt = $resp->json('expires_at'); // ISO8601

        if (!is_string($token) || $token === '') {
            throw new \RuntimeException('Installation token response missing token.');
        }

        // Cache token until shortly before expiry
        $ttlSeconds = 50 * 60;
        if (is_string($expiresAt) && $expiresAt !== '') {
            $ttlSeconds = max(60, now()->diffInSeconds(Carbon::parse($expiresAt), false) - 60);
        }

        Cache::put($cacheKey, $token, $ttlSeconds);

        return $token;
    }
}
