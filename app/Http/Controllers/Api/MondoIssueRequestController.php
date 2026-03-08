<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Curation;
use App\Models\MondoIssueRequest;
use App\Services\GitHub\GitHubIssuesClient;
use Illuminate\Http\Request;
use App\Services\Mondo\MondoNtrPayloadFactory;
use App\Services\Mondo\MondoNtrTemplateRenderer;
use Illuminate\Support\Str;
use App\Services\Mondo\MondoNtrParsing;

class MondoIssueRequestController extends Controller
{
    public function storeNewTerm(
        Request $request, 
        Curation $curation, 
        MondoNtrPayloadFactory $payloadFactory,
        MondoNtrTemplateRenderer $renderer,
        GitHubIssuesClient $github
    )
    {       
        $normalizedPmids = MondoNtrParsing::normalizePmids($curation->pmids ?? []);
        $request->merge(['curation_pmids' => $normalizedPmids]);

        $data = $request->validate([
            'label' => 'required|string|max:255',
            'definition' => 'required|string|max:10000',
            'parent' => 'required|string|max:255',
            'synonyms' => 'nullable|string|max:5000',
            'synonym_type' => 'nullable|in:exact,broad,narrow,related',
            'children' => 'nullable|string|max:5000',
            'orcid' => 'nullable|string|max:255',
            // 'website' => 'nullable|string|max:500',
            'comments' => 'nullable|string|max:10000',
            'curation_pmids' => 'required|array|min:1',
            'curation_pmids.*' => 'string',
        ]);

        

        $reqUuid = (string) Str::uuid();
        $payload = $payloadFactory->makeFromUi($data, $curation, $reqUuid);
        $title = $payloadFactory->makeDefaultTitle($payload);

        $record = MondoIssueRequest::create([
            'uuid' => $reqUuid,
            'curation_id' => $curation->id,
            'request_type' => 'new_term',
            'title' => $title,
            'body_markdown' => '',
            'payload_json' => $payload,
            'github_owner' => config('mondo.github.owner'),
            'github_repo' => config('mondo.github.repo'),
            'status' => 'draft',
        ]);

         try {
            $body = $renderer->render($payload);
            $record->update(['body_markdown' => $body]);

            // submit to GitHub
            $issue = $github->createIssue($title, $body);
            $record->update([
                'github_issue_number' => $issue['number'] ?? null,
                'github_issue_url' => $issue['html_url'] ?? null,
                'github_state' => $issue['state'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
                'last_error' => null,
            ]);

            return response()->json([
                'uuid' => $record->uuid,
                'github_issue_number' => $record->github_issue_number,
                'github_issue_url' => $record->github_issue_url,
                'github_state' => $record->github_state,
            ], 201);

        } catch (\Throwable $e) {
            $record->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'submitted_at' => now(),
            ]);
            throw $e;
        }
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
