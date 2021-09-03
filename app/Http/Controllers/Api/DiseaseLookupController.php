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
            'mondo_id' => 'required|regex:/(MONDO:)?\d{7}/'
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Disease::findByMondoIdOrFail($mondoId);
    }

    public function search(Request $request)
    {
        $queryString = strtolower(($request->query_string ?? ''));
        if (strlen($queryString) < 3) {
            return [];
        }
        $results = Disease::search($queryString)->limit(250)->get();

        return $results->toArray();
    }
    
    
}
