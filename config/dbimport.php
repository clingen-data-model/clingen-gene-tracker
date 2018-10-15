<?php

return [
    'beforeImport' => '\App\DbImport\ImportHooks@beforeImport',
    'afterImport' => '\App\DbImport\ImportHooks@afterImport',
    'ignoreTables' => null,
    'whitelist' => null,
    'prod_db' => [
        'host' => env('PROD_DB_HOST'),
        'database' => env('PROD_DB_DATABASE'),
        'username' => env('PROD_DB_USERNAME'),
        'password' => env('PROD_DB_PASSWORD'),
    ],
    'demo_db' => [
        'host' => env('DEMO_DB_HOST'),
        'database' => env('DEMO_DB_DATABASE'),
        'username' => env('DEMO_DB_USERNAME'),
        'password' => env('DEMO_DB_PASSWORD'),
    ]
];
