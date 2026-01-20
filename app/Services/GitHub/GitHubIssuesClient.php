<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\Http;

class GitHubIssuesClient
{
    public function __construct(private GitHubAppAuthService $auth) {}

    public function createIssue(string $title, string $body): array
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

    public function getIssue(int $issueNumber): array
    {
        $owner = (string) config('github_mondo.owner');
        $repo  = (string) config('github_mondo.repo');

        if ($owner === '' || $repo === '') {
            throw new \RuntimeException('Missing GitHub repo config (owner/repo).');
        }

        $token = $this->auth->getInstallationToken();

        $resp = Http::withToken($token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'GeneTracker',
            ])
            ->get("https://api.github.com/repos/{$owner}/{$repo}/issues/{$issueNumber}");

        if (!$resp->successful()) {
            throw new \RuntimeException('Failed to fetch issue: '.$resp->status().' '.$resp->body());
        }

        return $resp->json();
    }

    public function listInstallationRepositories(): array
    {
        $token = $this->auth->getInstallationToken();

        $resp = Http::withToken($token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'GeneTracker',
            ])
            ->get('https://api.github.com/installation/repositories');

        if (!$resp->successful()) {
            throw new \RuntimeException('Failed to list installation repositories: '.$resp->status().' '.$resp->body());
        }

        return $resp->json('repositories') ?? [];
    }


}
