<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MondoClingenLabelController extends Controller
{
    public function show(): Response
    {
        $path = 'mondo/clingen_preferred_label.tsv';

        if (!Storage::disk('public')->exists($path)) {
            return response('TSV not generated yet.', 404);
        }

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => 'text/tab-separated-values; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="clingen_preferred_label.tsv"',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

}
