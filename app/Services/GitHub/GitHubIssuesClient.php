<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\Http;

class GitHubIssuesClient
{
    public function __construct(private GitHubAppAuthService $auth) {}

    public function createIssue(string $title, string $body, array $labels = []): array
    {
        $owner = (string) config('github_mondo.owner');
        $repo  = (string) config('github_mondo.repo');

        if ($owner === '' || $repo === '') {
            throw new \RuntimeException('Missing GitHub repo config (owner/repo).');
        }

        $token = $this->auth->getInstallationToken();

        $payload = [
            'title' => $title,
            'body' => $body,
        ];

        if (!empty($labels)) {
            $payload['labels'] = array_values($labels);
        }

        $resp = Http::withToken($token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'GeneTracker',
            ])
            ->post("https://api.github.com/repos/{$owner}/{$repo}/issues", $payload);

        if (!$resp->successful()) {
            throw new \RuntimeException('Failed to create issue: '.$resp->status().' '.$resp->body());
        }

        return $resp->json();
    }
}
