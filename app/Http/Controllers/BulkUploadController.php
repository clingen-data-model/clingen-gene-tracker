<?php

namespace App\Http\Controllers;

use App\Exceptions\BulkUploads\InvalidFileException;
use App\Exceptions\DuplicateBulkCurationException;
use App\Http\Requests\BulkUploadRequest;
use App\Services\BulkCurationProcessor;

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
        if ($request->bulk_curations) {
            $path = $request->file('bulk_curations')->store('bulk_curation_uploads');

            try {
                $newCurations = $this->processor->processFile(storage_path('app/'.$path), $request->expert_panel_id);
                \Log::debug('got new curations');

                return view('bulk_uploads.show', compact('newCurations'));
            } catch (InvalidFileException $e) {
                \Log::debug('Invalid bulk file: '.$e->getMessage());
                $errors = $e->getValidationErrors();

                return view('bulk_uploads.show', compact('errors'));
            } catch (DuplicateBulkCurationException $e) {
                \Log::debug('Duplicate Bulk Curations: '.$e->getMessage());
                $duplicates = $e->duplicates;
                $expert_panel_id = $request->expert_panel_id;

                return view('bulk_uploads.show', compact('duplicates', 'path', 'expert_panel_id'));
            }
        }

        if ($request->path && storage_path(file_exists($request->path))) {
            $newCurations = $this->processor->processWithDuplicates(storage_path('app/'.$request->path), $request->expert_panel_id);

            return view('bulk_uploads.show', compact('newCurations'));
        }
    }
}
