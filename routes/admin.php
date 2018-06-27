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
    Route::get('/user/{id}/deactivate', 'UserCrudController@deactivate')->name('user-deactivate');
    CRUD::resource('user', 'UserCrudController');
    CRUD::resource('expert-panel', 'ExpertPanelCrudController');
    CRUD::resource('curation-status', 'CurationStatusCrudController');
    CRUD::resource('working-group', 'WorkingGroupCrudController');
    CRUD::resource('curation-type', 'CurationTypeCrudController');
    CRUD::resource('rationale', 'RationaleCrudController');
});
