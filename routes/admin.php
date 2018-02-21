<?php

Route::group([
    'middleware' => [
        'auth',
        'role:programmer|admin'
    ],
    'namespace' => 'Admin',
    'prefix' => 'admin'
], function () {
    Route::get('dashboard', '\Backpack\Base\app\Http\Controllers\AdminController@dashboard')->name('backpack.dashboard');
    Route::get('/', '\Backpack\Base\app\Http\Controllers\AdminController@redirect')->name('backpack');
    CRUD::resource('user', 'UserCrudController');
});
