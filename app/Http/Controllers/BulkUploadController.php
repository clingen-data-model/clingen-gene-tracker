<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use App\Services\BulkCurationProcessor;
use App\Http\Requests\BulkUploadRequest;
use App\Exceptions\BulkUploads\InvalidFileException;

class BulkUploadController extends Controller
{
    protected $processor;

    public function __construct(BulkCurationProcessor $processor)
    {
        $this->processor = $processor;
    }



    public function show()
    {
        return view('bulk_uploads.show');
    }

    public function upload(BulkUploadRequest $request)
    {
        $path = $request->file('bulk_curations')->store('bulk_curation_uploads');

        try {
            $newCurations = $this->processor->processFile(storage_path('app/'.$path), $request->expert_panel_id);
            return view('bulk_uploads.show', compact('newCurations'));
        } catch (InvalidFileException $e) {
            $errors = $e->getValidationErrors();
            return view('bulk_uploads.show', compact('errors'));
        }
    }
}
