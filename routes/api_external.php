<?php

Route::group([
    'middleware' => ['auth:api-external'],
], function () {
    Route::get('user', function () {
        return ['user' => request()->user()];
    });
});
