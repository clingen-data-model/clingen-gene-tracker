<?php

namespace App\Http\Controllers\Api;

use App\Classification;
use App\Curation;
use App\Http\Controllers\Controller;
use App\Http\Requests\CurationClassificationRequest;
use App\Jobs\Curations\AddClassification;
use App\Jobs\Curations\UpdateClassification;
use Illuminate\Http\JsonResponse;

class CurationClassificationController extends Controller
{
    public function index($curationId)
    {
        $curation = Curation::findOrFail($curationId);

        return $curation->classifications;
    }

    public function store(CurationClassificationRequest $request, $curationId)
    {
        $curation = Curation::findOrFail($curationId);
        $classification = Classification::find($request->classification_id);

        AddClassification::dispatchSync($curation, $classification, $request->classification_date);

        return $curation->classifications()->orderByDesc('classification_curation.created_at')->first();
    }

    public function show($curationId, $curationClassificationId)
    {
        return Curation::findOrFail($curationId)
            ->classifications()
            ->where('classification_curation.id', $curationClassificationId)
            ->first();
    }

    public function update(CurationClassificationRequest $request, $curationId, $curationClassificationId)
    {
        $curation = Curation::findOrFail($curationId);

        UpdateClassification::dispatchSync($curation, $curationClassificationId, $request->all());

        return $curation->classifications()
            ->where('classification_curation.id', $curationClassificationId)
            ->orderByDesc('classification_curation.updated_at')
            ->first();
    }

    public function destroy($curationId, $curationClassificationId): JsonResponse
    {
        $curation = Curation::findOrFail($curationId);

        $curation->classifications
            ->firstWhere('pivot.id', $curationClassificationId)
            ->pivot
            ->delete();

        return response()->json([], 204);
    }
}
