<?php

use Illuminate\Http\Request;

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
    Route::resource('/expert-panels', 'ExpertPanelController');
    Route::resource('/topics', 'TopicController');
    Route::get('/omim/entry', 'OmimController@entry');
    Route::get('/omim/search', 'OmimController@search');
});
