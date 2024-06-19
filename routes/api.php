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

Route::group([
    'middleware' => ['auth:api'],
    'namespace' => 'Api',
], function () {
    Route::get('/features', 'FeaturesController@index');
    Route::resource('/expert-panels', 'ExpertPanelController');
    // Resources

    Route::post('/curations/{id}/owner', 'CurationTransferController@store');
    Route::resource('/curations/{id}/classifications', 'CurationClassificationController')
        ->only(['index', 'store', 'update', 'destroy'])->name('index', 'curations.classifications.index');
    Route::resource('/curations/{id}/statuses', 'CurationCurationStatusController');

    Route::get('curations/{curation_id}/uploads/{upload_id}/file', 'CurationUploadController@getFile')->name('curation-upload-file');
    Route::resource('curations/{curation_id}/uploads', 'CurationUploadController')->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::resource('/curations', 'CurationController');

    Route::get('users/current', 'UserController@currentUser')->name('current-user');
    Route::resource('/users', 'UserController')->only(['index']);
    Route::resource('/curation-statuses', 'CurationStatusController')->only(['index']);
    Route::resource('/working-groups', 'WorkingGroupController')->only(['index', 'show']);
    Route::resource('/curation-types', 'CurationTypeController')->only(['index']);
    Route::resource('/rationales', 'RationaleController')->only(['index']);
    Route::resource('/classifications', 'ClassificationController')->only(['index']);
    Route::resource('/mois', 'MoiController')->only(['index']);
    Route::post('/bulk-lookup', 'BulkLookupController@data');
    Route::post('/bulk-lookup/csv', 'BulkLookupController@download');

    // OMIM
    Route::get('/omim/entry', 'OmimController@entry');
    Route::get('/omim/search', 'OmimController@search');
    Route::get('/omim/gene/{geneSymbol}', 'OmimController@gene');
    Route::get('/omim/curation/{curationId}', 'OmimController@forCuration');

    // Diseases
    Route::get('/diseases/search', 'DiseaseLookupController@search');
    Route::get('/diseases/{mondoId}', 'DiseaseLookupController@show');

    // Genes
    Route::post('/genes', 'GeneController@index');
    Route::post('/genes/csv', 'GeneController@download');


    /*
    * Catch-all route for generic API read exposure
    **/

    // index
    Route::get('{model}', 'DefaultApiController@index');

    // show
    Route::get('{model}/{id}', 'DefaultApiController@show');
});

