<?php

namespace App\Http\Controllers;

use App\ExpertPanel;
use App\CurationExporter;
use Illuminate\Http\Request;
use Illuminate\Filesystem\FilesystemManager;

class CurationExportController extends Controller
{
    public function __construct(
        private CurationExporter $exporter, 
    ) {}



    public function getCsv(Request $request)
    {
        return response()->download($this->exporter->getCsv($request->all()));
    }
}
