<?php

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    // 'middleware' => ['web', 'auth', 'role:admin|programmer'],
    'middleware' => ['web', 'role:admin|programmer', config('backpack.base.middleware_key')],
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes`
    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->name('logs');
    // Route::get('dashboard', '\Backpack\Base\app\Http\Controllers\AdminController@dashboard')
    //     ->name('backpack.dashboard');
    // Route::get('/', '\Backpack\Base\app\Http\Controllers\AdminController@redirect')
    //     ->name('backpack');

    Route::get('/user/{id}/deactivate', 'UserCrudController@deactivate')
        ->name('user-deactivate');

    Route::get('/user/{id}/reactivate', 'UserCrudController@reactivate')
        ->name('user-reactivate');

        Route::crud('user', 'UserCrudController');
        Route::crud('aff', 'AffiliationCrudController');
        Route::crud('expert-panel', 'ExpertPanelCrudController');
        Route::crud('curation-status', 'CurationStatusCrudController');
        Route::crud('working-group', 'WorkingGroupCrudController');
        Route::crud('curation-type', 'CurationTypeCrudController');
        Route::crud('rationale', 'RationaleCrudController');
        Route::crud('email', 'EmailCrudController');
        Route::crud('notification', 'NotificationCrudController');
        Route::crud('upload-category', 'UploadCategoryCrudController');
        Route::crud('moi', 'MoiCrudController');
    // Route::group([
//     'prefix'     => config('backpack.base.route_prefix', 'admin'),
//     'middleware' => ['web', 'auth'],
//     // 'middleware' => array_merge(
//     //     (array) config('backpack.base.web_middleware', 'web'),
//     //     (array) config('backpack.base.middleware_key', 'admin')
//     // ),
//     'namespace'  => 'App\Http\Controllers\Admin',
// ], function () { // custom admin routes
}); // this should be the absolute last line of this file
