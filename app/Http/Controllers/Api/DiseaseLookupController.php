<?php

namespace App\Http\Controllers\Api;

use App\Disease;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\MondoIdsRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DiseaseLookupController extends Controller
{
    public function show($mondoId)
    {
        $validator = Validator::make(['mondo_id' => $mondoId], [
            'mondo_id' => 'required|regex:/^(MONDO:)?\d{7}$/'
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        return Disease::findByMondoIdOrFail($mondoId);
    }

    
    /**
     * POST /diseases/mondos
     * Body accepts:
     *  - { "mondo_ids": ["MONDO:0000413", "MONDO:0000414"] }
     * Returns a collection (even for a single id), preserving input order.
     */
    public function lookupByMondo(MondoIdsRequest $request)
    {
        $data = $request->validated();
        $ids  = $data['mondo_ids']; // already canonical: MONDO:0000000

        $items = Disease::query()
            ->select('id', 'name', 'mondo_id', 'doid_id', 'is_obsolete', 'replaced_by')
            ->whereIn('mondo_id', $ids)
            ->get();

        // Preserve input order
        $order = array_flip($ids);
        return $items->sortBy(fn ($d) => $order[$d->mondo_id] ?? PHP_INT_MAX)->values();
    }

    public function search(Request $request)
    {
        $queryString = strtolower($request->input('query_string', ''));
        if (strlen($queryString) < 3) {
            return [];
        }
        $limit = (int) $request->input('limit', 250);
        return Disease::search($queryString)->limit($limit)->get()->toArray();
    }

    /**
     * POST /diseases/ontology
     * Body: { "ontology_id": "MONDO:0000413" } or { "ontology_id": "DOID:12345" }
     * Returns a minimal record with the matched ontology id.
     */
    public function getDiseaseByOntologyID(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ontology_id' => ['required', 'regex:/^(MONDO|DOID):\d+$/i'],
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $ontologyId = strtoupper($validator->validated()['ontology_id']);
        [$prefix]   = explode(':', $ontologyId, 2);
        $prefix     = strtoupper($prefix);
        return Disease::selectRaw("'{$prefix}' AS ontology, `{$prefix}_id` AS ontology_id, `name`")
            ->where("{$prefix}_id", $ontologyId)
            ->firstOrFail();
    }
}
