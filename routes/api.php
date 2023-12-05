<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {
    Route::get('/features', [Api\FeaturesController::class, 'index']);
    Route::resource('/expert-panels', Api\ExpertPanelController::class);
    // Resources

    Route::post('/curations/{id}/owner', [Api\CurationTransferController::class, 'store']);
    Route::resource('/curations/{id}/classifications', Api\CurationClassificationController::class)
        ->only(['index', 'store', 'update', 'destroy'])->name('index', 'curations.classifications.index');
    Route::resource('/curations/{id}/statuses', Api\CurationCurationStatusController::class);

    Route::get('curations/{curation_id}/uploads/{upload_id}/file', [Api\CurationUploadController::class, 'getFile'])->name('curation-upload-file');
    Route::resource('curations/{curation_id}/uploads', Api\CurationUploadController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::resource('/curations', Api\CurationController::class);

    Route::get('users/current', [Api\UserController::class, 'currentUser'])->name('current-user');
    Route::resource('/users', Api\UserController::class)->only(['index']);
    Route::resource('/curation-statuses', Api\CurationStatusController::class)->only(['index']);
    Route::resource('/working-groups', Api\WorkingGroupController::class)->only(['index', 'show']);
    Route::resource('/curation-types', Api\CurationTypeController::class)->only(['index']);
    Route::resource('/rationales', Api\RationaleController::class)->only(['index']);
    Route::resource('/classifications', Api\ClassificationController::class)->only(['index']);
    Route::resource('/mois', Api\MoiController::class)->only(['index']);
    Route::post('/bulk-lookup', [Api\BulkLookupController::class, 'data']);
    Route::post('/bulk-lookup/csv', [Api\BulkLookupController::class, 'download']);

    // OMIM
    Route::get('/omim/entry', [Api\OmimController::class, 'entry']);
    Route::get('/omim/search', [Api\OmimController::class, 'search']);
    Route::get('/omim/gene/{geneSymbol}', [Api\OmimController::class, 'gene']);

    // Diseases
    Route::get('/diseases/search', [Api\DiseaseLookupController::class, 'search']);
    Route::get('/diseases/{mondoId}', [Api\DiseaseLookupController::class, 'show']);

    // Genes
    Route::post('/genes', [Api\GeneController::class, 'index']);
    Route::post('/genes/csv', [Api\GeneController::class, 'download']);

    /*
    * Catch-all route for generic API read exposure
    **/

    // index
    Route::get('{model}', [Api\DefaultApiController::class, 'index']);

    // show
    Route::get('{model}/{id}', [Api\DefaultApiController::class, 'show']);
});
