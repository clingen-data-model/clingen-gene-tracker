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
        private FilesystemManager $fsManager
    ) {}



    public function getCsv(Request $request)
    {
        $csvPath = $this->exporter->getCsv($request->all());
        return response()->download($csvPath);
    }
}
