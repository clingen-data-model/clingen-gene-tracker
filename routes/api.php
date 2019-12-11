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
    'namespace' => 'Api'
], function () {
    
    // Resources
    Route::resource('/expert-panels', 'ExpertPanelController');
    Route::resource('/curations/{id}/classifications', 'CurationClassificationController')
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/curations/{id}/statuses', 'CurationCurationStatusController');
    Route::resource('/curations', 'CurationController');
    Route::resource('/users', 'UserController')->only(['index']);
    Route::resource('/curation-statuses', 'CurationStatusController')->only(['index']);
    Route::resource('/working-groups', 'WorkingGroupController')->only(['index', 'show']);
    Route::resource('/curation-types', 'CurationTypeController')->only(['index']);
    Route::resource('/rationales', 'RationaleController')->only(['index']);
    Route::resource('/classifications', 'ClassificationController')->only(['index']);

    // OMIM
    Route::get('/omim/entry', 'OmimController@entry');
    Route::get('/omim/search', 'OmimController@search');
    Route::get('/omim/gene/{geneSymbol}', 'OmimController@gene');
});
