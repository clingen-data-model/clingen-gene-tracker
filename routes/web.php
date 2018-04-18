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
    Route::get('test', function () {
        return view('test');
    });
    Route::get('/omim/entry', 'Api\OmimController@entry');
    Route::get('/omim/search', 'Api\OmimController@search');
    Route::get('/omim/gene', 'Api\OmimController@gene');

    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->name('logs');
});

Auth::routes();

$return403 = function () {
    $data['title'] = '403';
    $data['name'] = 'Registration is closed';

    return response()
        ->view('errors.403', $data, 403);
};

Route::get('/register', $return403);
Route::post('/register', $return403);
