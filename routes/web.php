<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware'=>'auth'], function () {
    Route::get('/', function () {
        return view('main');
    });
    Route::get('/omim/entry', 'Api\OmimController@entry');
    Route::get('/omim/search', 'Api\OmimController@search');
});

Route::group(['prefix' => config('backpack.base.route_prefix'), 'middleware' => ['admin'], 'namespace' => 'Admin'], function(){
    CRUD::resource('user', 'UserCrudController');
});

Auth::routes();
