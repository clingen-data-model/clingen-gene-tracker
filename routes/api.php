<?php

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

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FeaturesController;
use App\Http\Controllers\Api\ExpertPanelController;
use App\Http\Controllers\Api\CurationTransferController;
use App\Http\Controllers\Api\CurationClassificationController;
use App\Http\Controllers\Api\CurationCurationStatusController;
use App\Http\Controllers\Api\CurationUploadController;
use App\Http\Controllers\Api\CurationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CurationStatusController;
use App\Http\Controllers\Api\WorkingGroupController;
use App\Http\Controllers\Api\CurationTypeController;
use App\Http\Controllers\Api\RationaleController;
use App\Http\Controllers\Api\ClassificationController;
use App\Http\Controllers\Api\MoiController;
use App\Http\Controllers\Api\BulkLookupController;
use App\Http\Controllers\Api\OmimController;
use App\Http\Controllers\Api\DiseaseLookupController;
use App\Http\Controllers\Api\GeneController;
use App\Http\Controllers\Api\DefaultApiController;
use App\Http\Controllers\Api\UploadCategoryController;

Route::middleware('client')->prefix("client/v1")->group(function () {
    // Diseases
    Route::post('/diseases/search', [DiseaseLookupController::class, 'search']);
    Route::post('/diseases/mondos', [DiseaseLookupController::class, 'lookupByMondo']);
    Route::post('/diseases/ontology', [DiseaseLookupController::class, 'getDiseaseByOntologyID']);

    Route::post('/genes/search', [GeneController::class, 'searchPost']);
    Route::post('/genes/availability', [GeneController::class, 'index']);
    Route::post('/genes/byid', [GeneController::class, 'getGeneSymbolByID']);
    Route::post('/genes/bysymbol', [GeneController::class, 'getGeneSymbolBySymbol']);
    Route::post('/bulk-lookup', [BulkLookupController::class, 'data']);

    Route::get('/curations', [CurationController::class, 'index']);
    Route::get('/mois', [MoiController::class, 'index']);
});

Route::middleware(['auth:api'])->group(function () {
    

    Route::get('/features', [FeaturesController::class, 'index']);

    Route::resource('/expert-panels', ExpertPanelController::class);

    // Archived curations
    Route::patch('/curations/{curation}/archive', [CurationController::class, 'archive']);
    Route::patch('/curations/{curation}/unarchive', [CurationController::class, 'unarchive']);
    Route::get('/curations/archived-curation-options', [CurationController::class, 'searchArchivedCurations']);

    Route::post('/curations/{id}/owner', [CurationTransferController::class, 'store']);
    Route::resource('/curations/{id}/classifications', CurationClassificationController::class)->only(['index', 'store', 'update', 'destroy'])->names(['index' => 'curations.classifications.index']);
    Route::resource('/curations/{id}/statuses', CurationCurationStatusController::class);
    Route::get('curations/{curation_id}/uploads/{upload_id}/file', [CurationUploadController::class, 'getFile'])->name('curation-upload-file');
    Route::resource('curations/{curation_id}/uploads', CurationUploadController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('/curations', CurationController::class);

    Route::get('users/current', [UserController::class, 'currentUser'])->name('current-user');
    Route::resource('/users', UserController::class)->only(['index']);
    Route::resource('/curation-statuses', CurationStatusController::class)->only(['index']);
    Route::resource('/working-groups', WorkingGroupController::class)->only(['index', 'show']);
    Route::resource('/curation-types', CurationTypeController::class)->only(['index']);

    Route::prefix('admin')->middleware('role:admin|programmer')->group(function () {
        Route::get('/curation-types', [CurationTypeController::class, 'adminIndex']);
        Route::post('/curation-types', [CurationTypeController::class, 'store']);
        Route::put('/curation-types/{curation_type}', [CurationTypeController::class, 'update']);
        Route::delete('/curation-types/{curation_type}', [CurationTypeController::class, 'destroy']);
        Route::get('/rationales', [RationaleController::class, 'adminIndex']);
        Route::post('/rationales', [RationaleController::class, 'store']);
        Route::put('/rationales/{rationale}', [RationaleController::class, 'update']);
        Route::delete('/rationales/{rationale}', [RationaleController::class, 'destroy']);
        Route::get('/curation-statuses', [CurationStatusController::class, 'adminIndex']);
        Route::post('/curation-statuses', [CurationStatusController::class, 'store']);
        Route::put('/curation-statuses/{curation_status}', [CurationStatusController::class, 'update']);
        Route::delete('/curation-statuses/{curation_status}', [CurationStatusController::class, 'destroy']);
        Route::get('/upload-categories', [UploadCategoryController::class, 'adminIndex']);
        Route::post('/upload-categories', [UploadCategoryController::class, 'store']);
        Route::put('/upload-categories/{upload_category}', [UploadCategoryController::class, 'update']);
        Route::delete('/upload-categories/{upload_category}', [UploadCategoryController::class, 'destroy']);
        Route::get('/mois', [MoiController::class, 'adminIndex']);
        Route::put('/mois/{moi}', [MoiController::class, 'adminUpdate']);
        Route::get('/working-groups', [WorkingGroupController::class, 'adminIndex']);
        Route::post('/working-groups', [WorkingGroupController::class, 'store']);
        Route::put('/working-groups/{working_group}', [WorkingGroupController::class, 'update']);
        Route::delete('/working-groups/{working_group}', [WorkingGroupController::class, 'destroy']);
    });
    Route::resource('/rationales', RationaleController::class)->only(['index']);
    Route::resource('/classifications', ClassificationController::class)->only(['index']);
    Route::resource('/mois', MoiController::class)->only(['index']);

    Route::post('/bulk-lookup', [BulkLookupController::class, 'data']);
    Route::post('/bulk-lookup/csv', [BulkLookupController::class, 'download']);

    // OMIM
    Route::get('/omim/entry', [OmimController::class, 'entry']);
    Route::get('/omim/search', [OmimController::class, 'search']);
    Route::get('/omim/gene/{geneSymbol}', [OmimController::class, 'gene']);
    Route::get('/omim/curation/{curationId}', [OmimController::class, 'forCuration']);

    // Diseases
    Route::get('/diseases/search', [DiseaseLookupController::class, 'search']);
    Route::get('/diseases/{mondoId}', [DiseaseLookupController::class, 'show']);

    // Genes
    Route::post('/genes', [GeneController::class, 'index']);
    Route::post('/genes/csv', [GeneController::class, 'download']);

    // Catch-all generic API exposure
    Route::get('{model}', [DefaultApiController::class, 'index']);
    Route::get('{model}/{id}', [DefaultApiController::class, 'show']);
});


