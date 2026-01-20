<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Curation;
use App\Models\MondoIssueRequest;
use App\Services\GitHub\GitHubIssuesClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MondoIssueRequestController extends Controller
{
    public function storeNewTerm(Request $request, Curation $curation, GitHubIssuesClient $github)
    {       
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'definition' => 'required|string|max:10000',
            'parent' => 'required|string|max:255',
            'synonyms' => 'nullable|string|max:5000',
            'synonym_type' => 'nullable|in:exact,broad,narrow,related',
            'children' => 'nullable|string|max:5000',
            'orcid' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:500',
            'comments' => 'nullable|string|max:10000',
        ]);


        $reqUuid = (string) Str::uuid();

        $curation->load('expertPanel.affiliation');
        $clingenId = $curation->expertPanel?->affiliation?->clingen_id;
        $submitter1 = $clingenId ? "https://clinicalgenome.org/affiliation/{$clingenId}/" : null;
        $submitter2 = $request->user()?->name; 
        $submitters = array_values(array_filter([$submitter1, $submitter2]));
        $data['submitters'] = $submitters;

        $geneSymbol = $curation->gene_symbol;
        $hgncId = $curation->hgnc_id;
        $pmids = $curation->pmids;

        $title = "Request for new term: {$data['label']}";
        $body  = $this->buildNewTermMarkdown($reqUuid, $curation, $data, $pmids);


        $owner = config('github_mondo.owner');
        $repo  = config('github_mondo.repo');

        try {
            $issue = $github->createIssue($title, $body);

            $record = MondoIssueRequest::create([
                'uuid' => $reqUuid,
                'curation_id' => $curation->id,
                'request_type' => 'new_term',
                'title' => $title,
                'body_markdown' => $body,
                'payload_json' => $data,
                'github_owner' => $owner,
                'github_repo' => $repo,
                'github_issue_number' => $issue['number'] ?? null,
                'github_issue_url' => $issue['html_url'] ?? null,
                'github_state' => $issue['state'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return response()->json([
                'uuid' => $record->uuid,
                'github_issue_number' => $record->github_issue_number,
                'github_issue_url' => $record->github_issue_url,
                'github_state' => $record->github_state,
            ], 201);
        } catch (\Throwable $e) {
            MondoIssueRequest::create([
                'uuid' => $reqUuid,
                'curation_id' => $curation->id,
                'request_type' => 'new_term',
                'title' => $title,
                'body_markdown' => $body,
                'payload_json' => $data,
                'github_owner' => $owner,
                'github_repo' => $repo,
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'submitted_at' => now(),
            ]);

            throw $e;
        }
    }

    private function suggestLabel(string $geneSymbol, ?string $parentTerm): string
    {
        $parentTerm = trim((string) $parentTerm);
        return $parentTerm !== ''
            ? "{$geneSymbol}-related {$parentTerm}"
            : "{$geneSymbol}-related disease";
    }

    private function buildNewTermMarkdown(string $reqUuid, Curation $curation, array $data, $pmids): string
    {
        $lines = [];

        // Optional: short header (keep it small)
        $lines[] = "Use this form to request a new ontology term be added to Mondo.";
        $lines[] = "";

        // YAML fields (in order)
        $lines[] = "### Label";
        $lines[] = $data['label'];
        $lines[] = "";

        $lines[] = "### Synonyms";
        $lines[] = $data['synonyms'] ?? "";
        $lines[] = "";

        $lines[] = "### Synonym type";
        $lines[] = $data['synonym_type'] ?? "";
        $lines[] = "";

        $lines[] = "### Definition";
        $lines[] = $data['definition'];
        $lines[] = "";

        $lines[] = "### Parent term";
        $lines[] = $data['parent'];
        $lines[] = "";

        $lines[] = "### Children term(s)";
        $lines[] = $data['children'] ?? "";
        $lines[] = "";

        $lines[] = "### ORCID Identifier";
        $lines[] = $data['orcid'] ?? "";
        $lines[] = "";

        $lines[] = "### Website URL";
        $lines[] = $data['website'] ?? "";
        $lines[] = "";

        $lines[] = "### Additional comments";
        $lines[] = $data['comments'] ?? "";
        $lines[] = "";

        // Add GT context at bottom (recommended)
        $lines[] = "---";
        $lines[] = "### GeneTracker context (auto)";
        $lines[] = "- GeneTracker Request UUID: `{$reqUuid}`";
        $lines[] = "- Curation UUID: `{$curation->uuid}`";
        $lines[] = "- Causal gene: **{$curation->gene_symbol}** (HGNC: **{$curation->hgnc_id}**)";
        $lines[] = "- PMIDs: " . $this->formatPmidsForMondo($pmids);

        // Submitters (optional but you already have it; add here)
        $submitters = $data['submitters'] ?? [];
        if (is_array($submitters) && count($submitters)) {
            $lines[] = "- Submitters:";
            foreach ($submitters as $s) {
                $s = trim((string)$s);
                if ($s !== '') $lines[] = "  - {$s}";
            }
        }

        return implode("\n", $lines);
    }
    
    private function formatPmidsForMondo($pmids): string
    {
        if ($pmids === null) {
            return '(none)';
        }

        if (is_string($pmids)) {
            $pmids = trim($pmids);
            return $pmids !== '' ? $pmids : '(none)';
        }

        $clean = collect($pmids)
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(function ($v) {
                $s = trim((string) $v);
                $s = preg_replace('/^PMID:/i', '', $s);
                $s = preg_replace('/\D+/', '', $s);
                return $s;
            })
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        if ($clean->isEmpty()) {
            return '(none)';
        }

        return $clean->map(fn($id) => "PMID:{$id}")->implode('|');
    }

    public function indexForCuration(Request $request, Curation $curation)
    {
        $q = MondoIssueRequest::query()
            ->where('curation_id', $curation->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        if ($request->filled('github_state')) {
            $q->where('github_state', $request->string('github_state'));
        }

        $items = $q->get()->map(fn(MondoIssueRequest $r) => [
            'uuid' => $r->uuid,
            'curation_id' => $r->curation_id,
            'request_type' => $r->request_type,

            'title' => $r->title,
            'status' => $r->status,
            'last_error' => $r->last_error,

            'github_owner' => $r->github_owner,
            'github_repo' => $r->github_repo,
            'github_issue_number' => $r->github_issue_number,
            'github_issue_url' => $r->github_issue_url,
            'github_state' => $r->github_state,

            'submitted_at' => optional($r->submitted_at)->toIso8601String(),
            'last_synced_at' => optional($r->last_synced_at)->toIso8601String(),
            'created_at' => optional($r->created_at)->toIso8601String(),
            'updated_at' => optional($r->updated_at)->toIso8601String(),
        ]);

        return [
            'curation_id' => $curation->id,
            'curation_uuid' => $curation->uuid,
            'mondo_requests' => $items,
        ];
    }

    public function show(MondoIssueRequest $mondoIssueRequest)
    {
         $r = $mondoIssueRequest;

        return [
            'uuid' => $r->uuid,
            'curation_id' => $r->curation_id,
            'request_type' => $r->request_type,

            'title' => $r->title,
            'body_markdown' => $r->body_markdown,
            'payload' => $r->payload_json,

            'status' => $r->status,
            'last_error' => $r->last_error,

            'github_owner' => $r->github_owner,
            'github_repo' => $r->github_repo,
            'github_issue_number' => $r->github_issue_number,
            'github_issue_url' => $r->github_issue_url,
            'github_state' => $r->github_state,

            'submitted_at' => optional($r->submitted_at)->toIso8601String(),
            'last_synced_at' => optional($r->last_synced_at)->toIso8601String(),
            'created_at' => optional($r->created_at)->toIso8601String(),
            'updated_at' => optional($r->updated_at)->toIso8601String(),
        ];
    }
}
