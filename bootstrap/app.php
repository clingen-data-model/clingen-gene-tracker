<?php

use Dotenv\Dotenv;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/
if (file_exists(__DIR__.'/../.env')) {
    Dotenv::create(__DIR__.'/..')->load();
}

if (env('APP_STORAGE_PATH')) {
    // get the current working directory b/c it could be frigging anywhere
    $cwd = getcwd();
    // change to the base path so we can handle paths relative to the location of .env
    chdir(base_path());
    // Get the real path in the .env
    $storagePath = realpath(base_path(env('APP_STORAGE_PATH', 'storage')));
    // change back to original working directory
    chdir($cwd);

    $app->useStoragePath($storagePath);
}


Dotenv::create(__DIR__.'/..')->load();
if (env('APP_STORAGE_PATH')) {
    // get the current working directory b/c it could be frigging anywhere
    $cwd = getcwd();
    // change to the base path so we can handle paths relative to the location of .env
    chdir(base_path());
    // Get the real path in the .env
    $storagePath = realpath(base_path(env('APP_STORAGE_PATH', 'storage')));
    // change back to original working directory
    chdir($cwd);

    $app->useStoragePath($storagePath);
}


return $app;
