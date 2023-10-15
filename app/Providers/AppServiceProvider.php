<?php

namespace App\Providers;

#use App\Logging\ContainerRoleProcessor;
use App\Rules\ValidGeneSymbolRule;
use App\Rules\ValidHgncGeneSymbol;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
 #       Log::pushProcessor(new ContainerRoleProcessor());

        date_default_timezone_set('America/New_York');
        if ($this->app->environment('production')) {
            config(['backpack.base.skin' => 'skin-blue']);
        }

        if ($this->app->environment('local', 'demo')) {
            config(['backpack.base.logo_lg' => '<b>ClinGen</b> - '.$this->app->environment()]);
        }

        if (config('app.url_scheme')) {
            URL::forceScheme(config('app.url_scheme'));
        }

        $this->app->bind(ValidGeneSymbolRule::class, ValidHgncGeneSymbol::class);

        $this->app->bind(ClientInterface::class, function () {
            return new Client();
        });

        $this->registerActionsAsCommands();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local', 'testing')) {
            // $this->app->register(\Laravel\Dusk\DuskServiceProvider::class);
            // $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    private function registerActionsAsCommands()
    {
        if ($this->app->runningInConsole()) {
            $actionsDirs = [
                'app/Actions',
                'app/DataExchange/Actions',
                'app/Gci/Actions',
            ];
            Actions::registerCommands($actionsDirs);
        }
    }
}
