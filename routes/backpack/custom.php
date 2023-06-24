<?php

use App\Actions\ApiClientDeleteToken;
use App\Http\Controllers\Admin\ApiClientCrudController;
use App\Http\Controllers\App;
use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::prefix(config('backpack.base.route_prefix', 'admin'))->middleware('web', 'role:admin|programmer', config('backpack.base.middleware_key'))->group(function () { // custom admin routes`
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->name('logs');
    Route::get('/user/{id}/deactivate', [App\Http\Controllers\Admin\UserCrudController::class, 'deactivate'])
        ->name('user-deactivate');

    Route::get('/user/{id}/reactivate', [App\Http\Controllers\Admin\UserCrudController::class, 'reactivate'])
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
    Route::crud('api-client', 'ApiClientCrudController');
    Route::get('api-client/{id}/create-token', [ApiClientCrudController::class, 'createToken']);
}); // this should be the absolute last line of this file

Route::prefix(config('backpack.base.route_prefix', 'admin'))->middleware('web', 'role:admin|programmer', config('backpack.base.middleware_key'))->group(function () {
    Route::delete('api-client-tokens/{id}', ApiClientDeleteToken::class);
});
