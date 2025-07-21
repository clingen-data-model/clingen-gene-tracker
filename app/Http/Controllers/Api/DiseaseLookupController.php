<?php

namespace App\Http\Controllers\Api;

use App\Disease;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

    public function getDiseaseByMondoID(Request $request)
    {        
        return $this->show($request->input('mondo_id'));
    }

    public function search(Request $request)
    {
        $queryString = strtolower(($request->query_string ?? ''));
        if (strlen($queryString) < 3) {
            return [];
        }
        $limit = (int) ($request->limit ?? 250);
        $results = Disease::search($queryString)->limit($limit)->get();

        return $results->toArray();
    }

    public function getDiseaseByMondoIDs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mondo_ids' => ['required', 'array', 'min:1'],
            'mondo_ids.*' => ['regex:/^MONDO:\d{7}$/']
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Disease::whereIn('mondo_id', $request->mondo_ids)
            ->select('id', 'name', 'mondo_id', 'doid_id', 'is_obsolete', 'replaced_by')
            ->get();
    }

    public function getDiseaseByOntologyID(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ontology_id' => ['required', 'regex:/^(MONDO:|DOID:)\d+$/']
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $ontologyId = $validator->validated()['ontology_id'];
        $ontology = strtoupper(explode(':', $ontologyId)[0]);
        return Disease::selectRaw("'{$ontology}' AS ontology, `{$ontology}_id` AS ontology_id, `name`")
                        ->where("{$ontology}_id", $ontologyId)
                        ->firstOrFail();
    }
}
