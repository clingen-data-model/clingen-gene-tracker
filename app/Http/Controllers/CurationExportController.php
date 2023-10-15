<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\CurationExporter;
use Illuminate\Http\Request;

class CurationExportController extends Controller
{
    protected $exporter;

    public function __construct(CurationExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    public function getCsv(Request $request): BinaryFileResponse
    {
        return response()->download($this->exporter->getCsv($request->all()));
    }
}
