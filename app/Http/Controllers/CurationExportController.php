<?php

namespace App\Http\Controllers;

use App\CurationExporter;
use Illuminate\Http\Request;

class CurationExportController extends Controller
{
    public function __construct(
        private CurationExporter $exporter, 
    ) {}



    public function getCsv(Request $request)
    {
        $this->exporter->getCsv($request->all());
        return response()->download($this->exporter->getCsv($request->all()));
    }
}
