<?php

use App\Http\Controllers\ExternalApi\ApiDocumentationController;
use App\Http\Controllers\ExternalApi\PrecurationResourceController;

Route::middleware('auth:api-external')->group(function () {
    Route::get('/', [ApiDocumentationController::class, 'index']);

    Route::get('user', function () {
        return ['user' => request()->user()];
    });

    Route::resource('pre-curations', PrecurationResourceController::class)->only('index', 'show');
});
