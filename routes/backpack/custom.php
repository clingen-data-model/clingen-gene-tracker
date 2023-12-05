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

    Route::crud('user', \App\Http\Controllers\Admin\UserCrudController::class);
    Route::crud('aff', \App\Http\Controllers\Admin\AffiliationCrudController::class);
    Route::crud('expert-panel', \App\Http\Controllers\Admin\ExpertPanelCrudController::class);
    Route::crud('curation-status', \App\Http\Controllers\Admin\CurationStatusCrudController::class);
    Route::crud('working-group', \App\Http\Controllers\Admin\WorkingGroupCrudController::class);
    Route::crud('curation-type', \App\Http\Controllers\Admin\CurationTypeCrudController::class);
    Route::crud('rationale', \App\Http\Controllers\Admin\RationaleCrudController::class);
    Route::crud('email', \App\Http\Controllers\Admin\EmailCrudController::class);
    Route::crud('notification', \App\Http\Controllers\Admin\NotificationCrudController::class);
    Route::crud('upload-category', \App\Http\Controllers\Admin\UploadCategoryCrudController::class);
    Route::crud('moi', \App\Http\Controllers\Admin\MoiCrudController::class);
    Route::crud('api-client', \App\Http\Controllers\Admin\ApiClientCrudController::class);
    Route::get('api-client/{id}/create-token', [ApiClientCrudController::class, 'createToken']);
}); // this should be the absolute last line of this file

Route::prefix(config('backpack.base.route_prefix', 'admin'))->middleware('web', 'role:admin|programmer', config('backpack.base.middleware_key'))->group(function () {
    Route::delete('api-client-tokens/{id}', ApiClientDeleteToken::class);
});
