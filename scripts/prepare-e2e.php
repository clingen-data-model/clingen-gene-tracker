<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

const E2E_DATABASE = 'genetracker_e2e';

function setEnvironmentValue(string $key, string $value): void
{
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

setEnvironmentValue('APP_ENV', 'testing');
setEnvironmentValue('APP_URL', 'http://127.0.0.1:8013');
setEnvironmentValue('DB_CONNECTION', 'testing');
setEnvironmentValue('DB_DATABASE_TEST', E2E_DATABASE);
setEnvironmentValue('SESSION_DRIVER', 'file');
setEnvironmentValue('SESSION_COOKIE', 'genetracker_e2e_session');
setEnvironmentValue('CACHE_DRIVER', 'array');
setEnvironmentValue('QUEUE_CONNECTION', 'sync');
setEnvironmentValue('DX_ENABLE_PUSH', 'false');

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.connections.testing');
$configuredDatabase = $connection['database'] ?? null;

if ($configuredDatabase !== E2E_DATABASE) {
    fwrite(STDERR, "Refusing to reset unexpected database: {$configuredDatabase}\n");
    exit(1);
}

try {
    DB::connection('testing')->getPdo();
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'Unable to connect to the provisioned E2E database "'.E2E_DATABASE.'". '
        ."Create it and grant the configured Laravel database user access before running the E2E suite.\n"
        .'Database connection error: '.$exception->getMessage()."\n"
    );
    exit(1);
}

$exitCode = Artisan::call('migrate:fresh', [
    '--database' => 'testing',
    '--seed' => true,
    '--force' => true,
    '--no-interaction' => true,
]);
$output = Artisan::output();

if ($exitCode === 0) {
    $exitCode = Artisan::call('db:seed', [
        '--database' => 'testing',
        '--class' => Database\Seeders\E2ECurationsSeeder::class,
        '--force' => true,
        '--no-interaction' => true,
    ]);
    $output .= Artisan::output();
}

fwrite(STDOUT, $output);
exit($exitCode);
