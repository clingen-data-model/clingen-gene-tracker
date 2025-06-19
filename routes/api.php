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

use App\Http\Controllers\Api\client\DiseaseController as ClientDiseaseController;
use App\Http\Controllers\Api\client\GeneController as ClientGeneController;

Route::middleware('client')->prefix("client/v1")->group(function () {
    Route::get('/machine-access', function () {
        return response()->json(['message' => 'Authenticated as client']);
    });
        // Diseases
    Route::post('/diseases/search', [ClientDiseaseController::class, 'search']);
    Route::post('/diseases/mondo', [ClientDiseaseController::class, 'getDiseaseByMondoID']);
    Route::post('/diseases/ontology', [ClientDiseaseController::class, 'getDiseaseByOntologyID']);

    Route::post('/genes/search', [ClientGeneController::class, 'search']);
    Route::post('/genes/byid', [ClientGeneController::class, 'getGeneSymbolByID']);
    Route::post('/genes/bysymbol', [ClientGeneController::class, 'findHgncIdBySymbol']);
});

Route::middleware(['auth:api'])->group(function () {
    

    Route::get('/features', [FeaturesController::class, 'index']);

    Route::resource('/expert-panels', ExpertPanelController::class);

    Route::post('/curations/{id}/owner', [CurationTransferController::class, 'store']);

    Route::resource('/curations/{id}/classifications', CurationClassificationController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names(['index' => 'curations.classifications.index']);

    Route::resource('/curations/{id}/statuses', CurationCurationStatusController::class);

    Route::get('curations/{curation_id}/uploads/{upload_id}/file', [CurationUploadController::class, 'getFile'])
        ->name('curation-upload-file');

    Route::resource('curations/{curation_id}/uploads', CurationUploadController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::resource('/curations', CurationController::class);

    Route::get('users/current', [UserController::class, 'currentUser'])->name('current-user');
    Route::resource('/users', UserController::class)->only(['index']);
    Route::resource('/curation-statuses', CurationStatusController::class)->only(['index']);
    Route::resource('/working-groups', WorkingGroupController::class)->only(['index', 'show']);
    Route::resource('/curation-types', CurationTypeController::class)->only(['index']);
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


