<?php

Route::group([
    'middleware' => [
        'auth',
        'role:programmer|admin'
    ],
    'namespace' => 'Admin',
    'prefix' => 'admin'
], function () {
    CRUD::resource('user', 'UserCrudController');
});
