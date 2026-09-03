<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UploadCategoryRequest;
use App\Upload;
use App\UploadCategory;
use Illuminate\Http\Request;

class UploadCategoryController extends ApiController
{
    public function adminIndex()
    {
        return UploadCategory::query()->orderBy('name')->get();
    }

    public function store(UploadCategoryRequest $request)
    {
        return response()->json(UploadCategory::create($request->validated()), 201);
    }

    public function update(UploadCategoryRequest $request, UploadCategory $uploadCategory)
    {
        $uploadCategory->update($request->validated());

        return $uploadCategory->fresh();
    }

    public function destroy(Request $request, UploadCategory $uploadCategory)
    {
        if (Upload::withTrashed()->where('upload_category_id', $uploadCategory->id)->exists()) {
            return response()->json([
                'message' => 'This upload category is in use and cannot be deleted.',
            ], 409);
        }

        $uploadCategory->delete();

        return response()->noContent();
    }
}
