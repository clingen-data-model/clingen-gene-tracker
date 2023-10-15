<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

return [
    'name' => env('APP_NAME', 'ClinGen Tracker'),
    'env' => env('APP_ENV', 'production'),
    'container_role' => env('CONTAINER_ROLE', null),
    'debug' => env('APP_DEBUG', false),
    'storage_path' => env('APP_STORAGE_PATH'),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'omim_key' => env('OMIM_API_KEY'),
    'gci_api_key' => env('GCI_API_KEY'),
    'omim_cache_life' => 20 * 60,
    'url_scheme' => env('URL_SCHEME', null),
    'log_debug' => env('LOG_DEBUG', false),
    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\ComposerServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        // App\Providers\HorizonServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\ClientServiceProvider::class,

        App\DataExchange\DataExchangeServiceProvider::class,
        Lab404\Impersonate\ImpersonateServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        'Arr' => Arr::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Str' => Str::class,
    ])->toArray(),

    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID', null),
    'transfers_enabled' => env('TRANSFERS_ENABLED', false),
    'send-to-gci-enabled' => env('SEND_TO_GCI_ENABLED', false),
];
