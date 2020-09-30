<?php

/**
 * Laravel - A PHP Framework For Web Artisans.
 *
 * @author   Taylor Otwell <taylor@laravel.com>
 */
require_once __DIR__.'/../Profiling/TaskTimeSingleton.php';
require_once __DIR__.'/../Profiling/TaskTimeWriter.php';

$timer = Profiling\TaskTimeSingleton::init();
// var_dump($timer);
// die();
$timer->addEvent('public/index.php - initiaized public/index.php');

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';
$timer->addEvent('public/index.php - autoloaded dependencies');

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';
$timer->addEvent('public/index.php - bootstrapped application');

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$timer->addEvent('public/index.php - made the kernal');

$request = Illuminate\Http\Request::capture();
$timer->addEvent('public/index.php - captured the request');

$response = $kernel->handle($request);
$timer->addEvent('public/index.php - handled the request');

$response->send();
$timer->addEvent('public/index.php - sent the response');

$kernel->terminate($request, $response);
$timer->addEvent('public/index.php - terminated the kernal');

$writer = new Profiling\TaskTimeWriter($timer);
$writer->writeToFile();
