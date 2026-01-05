<?php

namespace App\Console\Commands;

use App\Models\MondoIssueRequest;
use App\Services\GitHub\GitHubIssuesClient;
use Illuminate\Console\Command;

class MondoSyncIssues extends Command
{
    protected $signature = 'mondo:sync-issues {--limit=50} {--uuid=}';
    protected $description = 'Sync GitHub issue state (open/closed) into mondo_issue_requests';

    public function handle(GitHubIssuesClient $github): int
    {
        $limit = (int) $this->option('limit');
        $uuid  = $this->option('uuid');

        $q = MondoIssueRequest::query()
            ->whereNotNull('github_issue_number')
            ->where('status', 'submitted')
            ->where(function ($qq) {
                $qq->whereNull('github_state')
                   ->orWhere('github_state', 'open');
            })
            ->orderBy('id');

        if ($uuid) {
            $q->where('uuid', $uuid);
        }

        $rows = $q->limit($limit)->get();
        if ($rows->isEmpty()) {
            $this->info('No open issues to sync.');
            return self::SUCCESS;
        }

        $updated = 0;

        foreach ($rows as $r) {
            try {
                $issue = $github->getIssue((int) $r->github_issue_number);

                $state = $issue['state'] ?? null;
                $url   = $issue['html_url'] ?? $r->github_issue_url;

                $r->github_state = $state;
                $r->github_issue_url = $url;
                $r->last_synced_at = now();
                $r->last_error = null;

                if ($state === 'closed') {
                    $r->status = 'resolved';
                }

                $r->save();

                $updated++;
                $this->line("Synced {$r->uuid}: #{$r->github_issue_number} => {$state}");
            } catch (\Throwable $e) {
                $r->last_error = $e->getMessage();
                $r->last_synced_at = now();
                $r->save();

                $this->error("Failed {$r->uuid}: ".$e->getMessage());
            }
        }

        $this->info("Done. Updated {$updated} record(s).");
        return self::SUCCESS;
    }
}
