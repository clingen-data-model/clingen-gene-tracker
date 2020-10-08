<?php

namespace App\Http\Controllers\Api;

use App\Curation;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUploadRequest;
use App\Http\Requests\CurationUploadIndexRequest;
use App\Http\Resources\UploadResource;
use App\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CurationUploadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CurationUploadIndexRequest $request, $curationId)
    {
        $validated = $request->validated();
        $query = Upload::query();

        if ($request->has('where')) {
            foreach ($validated['where'] as $key => $val) {
                $query->where($key, $val);
            }
        }

        if ($request->has('sort')) {
            $field = $validated->sort['field'] ?? 'name';
            $dir = $validated->sort['dir'] ?? 'asc';
            $query->orderBy($field, $dir);
        }

        if ($request->has('with')) {
            $query->with($validated['with']);
        }

        if ($request->has('with_deleted')) {
            $query->withTrashed();
        }

        $uploads = $query->get();

        return UploadResource::collection($uploads);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUploadRequest $request, $curationId)
    {
        $curation = Curation::findOrFail($curationId);
        if (!Auth::user()->hasAnyRole(['programmer', 'admin']) && !Auth::user()->inExpertPanel($curation->expertPanel)) {
            return response()->json(['error' => 'You do not have permission to create a document.'], 403);
        }

        $path = $request->file->store('public/curator_uploads');

        $originalFileName = $request->file->getClientOriginalName();

        $upload = Upload::create([
            'curation_id' => $request->curation_id,
            'name' => $request->name ?? $originalFileName,
            'file_name' => $originalFileName,
            'file_path' => $path,
            'upload_category_id' => $request->upload_category_id,
            'notes' => $request->notes,
        ]);
        $upload->load('category');

        return new UploadResource($upload);
    }

    /**
     * Display the specified resource.
     *
     * @param int $curationId
     * @param int $uploadId
     *
     * @return \Illuminate\Http\Response
     */
    public function show($curationId, $uploadId)
    {
        $upload = Upload::findOrFail($uploadId);
        $upload->load('category');

        return new UploadResource($upload);
    }

    public function getFile($curationId, $uploadId)
    {
        $upload = Upload::findOrFail($uploadId);
        $upload->load('category');

        if (!Auth::user()->can('view', $upload)) {
            return response('', 403);
        }

        if (!file_exists(storage_path('app/'.$upload->file_path))) {
            return response(null, 404);
        }

        return Storage::download($upload->file_path);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $curationId
     * @param int $uploadId
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $curationId, $uploadId)
    {
        $upload = Upload::find($uploadId);
        if (!Auth::user()->can('update', $upload)) {
            return response(null, 403);
        }

        $upload->update($request->except('curation_id'));
        $upload->load('category');

        return new UploadResource($upload);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $curationId
     * @param int $uploadId
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($curationId, $uploadId)
    {
        $upload = Upload::find($uploadId);
        if (!Auth::user()->can('delete', $upload)) {
            return response(null, 403);
        }

        $upload->delete();

        return response(null, 204);
    }
}
