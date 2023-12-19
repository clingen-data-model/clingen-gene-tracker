<?php

namespace App\Http\Controllers\Api;

use App\Curation;
use App\Classification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Curations\AddClassification;
use App\Jobs\Curations\UpdateClassification;
use App\Http\Requests\CurationClassificationRequest;

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
        
        return $curation->classifications()->orderBy('classification_curation.created_at', 'desc')->first();
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
                ->orderBy('classification_curation.updated_at', 'desc')
                ->first();
    }

    public function destroy($curationId, $curationClassificationId)
    {
        $curation = Curation::findOrFail($curationId);

        $curation->classifications
            ->firstWhere('pivot.id', $curationClassificationId)
            ->pivot
            ->delete();

        return response()->json([], 204);
    }
}
