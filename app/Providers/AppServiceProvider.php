<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Clients\OmimClient;
use App\Services\KafkaProducer;
use App\Services\MessageLogger;
use App\Contracts\MessagePusher;
use App\Services\DisabledPusher;
use Illuminate\Support\ServiceProvider;
use App\Contracts\OmimClient as OmimClientContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        date_default_timezone_set('America/New_York');
        if ($this->app->environment('production')) {
            config(['backpack.base.skin'=>'skin-blue']);
        }
        if ($this->app->environment('local', 'demo')) {
            config(['backpack.base.logo_lg' => '<b>ClinGen</b> - '.$this->app->environment()]);
        }

        $this->app->bind(OmimClientContract::class, OmimClient::class);

        $this->app->bind(MessagePusher::class, function () {
            if (! config('streaming-service.enable-push')) {
                return new DisabledPusher();
            }
            if (config('streaming-service.driver') == 'log') {
                return new MessageLogger();
            }
            return new KafkaProducer();
        });

        \Request::macro('dateParsed', function (...$dates) {
            return collect($this->all())
                    ->transform(function ($value, $key) use ($dates) {
                        if (in_array($key, $dates)) {
                            return Carbon::parse($value);
                        }

                        return $value;
                    })
                    ->toArray();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing')) {
            // $this->app->register(\Laravel\Dusk\DuskServiceProvider::class);
            // $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        $this->app->bind(
            'App\Contracts\OmimClient',
            'App\Clients\OmimClient'
        );
    }
}
