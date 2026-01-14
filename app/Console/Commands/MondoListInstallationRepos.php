<?php

namespace App\Console\Commands;

use App\Services\GitHub\GitHubIssuesClient;
use Illuminate\Console\Command;

class MondoListInstallationRepos extends Command
{
    protected $signature = 'mondo:list-installation-repos';
    protected $description = 'List repositories accessible by the configured GitHub App installation token';

    public function handle(GitHubIssuesClient $github): int
    {
        $repos = $github->listInstallationRepositories();

        if (empty($repos)) {
            $this->warn('No repositories returned for this installation.');
            return self::SUCCESS;
        }

        foreach ($repos as $r) {
            $fullName = $r['full_name'] ?? '(unknown)';
            $private  = $r['private'] ?? null;
            $this->line(sprintf(
                '- %s%s',
                $fullName,
                $private === true ? ' (private)' : ''
            ));
        }

        return self::SUCCESS;
    }
}
