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
            'parent_term' => 'nullable|string|max:255',
            'parent_term_id' => 'nullable|string|max:50',
            'association_references' => 'nullable|string|max:5000',
            'parent_classification_references' => 'nullable|string|max:5000',
            'use_suggested_label' => 'nullable|string|max:50',
            'requested_label' => 'nullable|string|max:255',
            'requested_synonyms' => 'nullable|string|max:5000',
            'requested_label_synonym_references' => 'nullable|string|max:5000',
            'agree_definition' => 'nullable|string|max:50',
            'definition_additions' => 'nullable|string|max:10000',
            'definition_references' => 'nullable|string|max:5000',
            'cross_references' => 'nullable|string|max:5000',
            'child_terms' => 'array',
            'child_terms.*.name' => 'nullable|string|max:255',
            'child_terms.*.mondo_id' => 'nullable|string|max:50',
            'child_terms.*.evidence' => 'nullable|string|max:5000',
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

        $suggestedLabel = $this->suggestLabel($geneSymbol, $data['parent_term'] ?? null);
        $requestedLabel = $data['requested_label'] ?? null;
        $labelToUse = ($requestedLabel && trim($requestedLabel) !== '') ? $requestedLabel : $suggestedLabel;

        $title = "[NTR] {$labelToUse}";
        $body = $this->buildNewTermMarkdown($reqUuid, $curation, $data, $suggestedLabel, $pmids);

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

    private function buildNewTermMarkdown(string $reqUuid, Curation $curation, array $data, string $suggestedLabel, array $pmids): string
    {
        $lines = [];

        $lines[] = "## GeneTracker MONDO New Term Request (POC)";
        $lines[] = "";
        $lines[] = "**GeneTracker Request UUID:** `{$reqUuid}`";
        $lines[] = "**Curation UUID:** `{$curation->uuid}`";
        $lines[] = "";

        $lines[] = "### Submitter(s)";
        $submitters = $data['submitters'] ?? [];
        if (!is_array($submitters) || count($submitters) === 0) {
            $lines[] = "- (auto-submitters unavailable)";
        } else {
            foreach ($submitters as $s) {
                $s = trim((string) $s);
                if ($s !== '') {
                    $lines[] = "- {$s}";
                }
            }
            if (count($submitters) > 0 && count(array_filter($submitters, fn($x) => trim((string)$x) !== '')) === 0) {
                $lines[] = "- (auto-submitters unavailable)";
            }
        }

        $lines[] = "";


        $lines[] = "### Causal gene";
        $lines[] = "- Gene symbol: **{$curation->gene_symbol}**";
        $lines[] = "- HGNC ID: **{$curation->hgnc_id}**";
        $lines[] = "";

        $lines[] = "### References for this gene–disease association";
        $lines[] = "- From GT curation pmids: " . $this->formatPmidsForMondo($pmids);
        if (!empty($data['association_references'])) {
            $lines[] = "- Provided: {$data['association_references']}";
        }
        $lines[] = "";

        $lines[] = "### Parent term classification";
        $lines[] = "- Parent term: " . ($data['parent_term'] ?? '(not provided)');
        $lines[] = "- Parent term ID: " . ($data['parent_term_id'] ?? '(not provided)');
        if (!empty($data['parent_classification_references'])) {
            $lines[] = "- Evidence: {$data['parent_classification_references']}";
        }
        $lines[] = "";

        $lines[] = "### Label";
        $lines[] = "- Automatically suggested label: **{$suggestedLabel}**";
        $lines[] = "- Use suggested label?: " . ($data['use_suggested_label'] ?? '(not specified)');
        $lines[] = "- Requested label: " . ($data['requested_label'] ?? '(none)');
        $lines[] = "";

        $lines[] = "### Synonyms";
        $lines[] = "- Requested synonym(s): " . ($data['requested_synonyms'] ?? '(none)');
        $lines[] = "- References for label/synonym(s): " . ($data['requested_label_synonym_references'] ?? '(none)');
        $lines[] = "";

        $lines[] = "### Definition";
        $lines[] = "- Agree with suggested definition?: " . ($data['agree_definition'] ?? '(not specified)');
        $lines[] = "- Additions / suggested definition: " . ($data['definition_additions'] ?? '(none)');
        $lines[] = "- Definition references: " . ($data['definition_references'] ?? '(none)');
        $lines[] = "";

        $lines[] = "### Cross references";
        $lines[] = $data['cross_references'] ?? '(none)';
        $lines[] = "";

        $lines[] = "### Child term(s)";
        $childTerms = $data['child_terms'] ?? [];
        if (is_array($childTerms) && count($childTerms)) {
            foreach ($childTerms as $i => $ct) {
                $lines[] = "**Child term " . ($i + 1) . "**";
                $lines[] = "- Name: " . ($ct['name'] ?? '(none)');
                $lines[] = "- MONDO ID: " . ($ct['mondo_id'] ?? '(none)');
                $lines[] = "- Evidence: " . ($ct['evidence'] ?? '(none)');
                $lines[] = "";
            }
        } else {
            $lines[] = "(none)";
        }

        return implode("\n", $lines);
    }

    private function formatPmidsForMondo(array $pmids): string
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
