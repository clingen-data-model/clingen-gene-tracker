<?php

namespace App\Console\Commands;

use App\Services\GitHub\GitHubIssuesClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MondoTestIssue extends Command
{
    protected $signature = 'mondo:test-issue {--title=}';
    protected $description = 'Create a test issue via GitHub App installation token';

    public function handle(GitHubIssuesClient $client): int
    {
        $uuid = (string) Str::uuid();
        $title = $this->option('title') ?: "[POC] GeneTracker MONDO issue test {$uuid}";
        $body = "Hello from GeneTracker.\n\nGeneTracker Request UUID: {$uuid}\n";

        $issue = $client->createIssue($title, $body);

        $this->info("Created issue #{$issue['number']}: {$issue['html_url']}");
        return self::SUCCESS;
    }
}
