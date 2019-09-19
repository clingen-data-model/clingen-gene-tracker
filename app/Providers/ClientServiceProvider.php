<?php

namespace App\Providers;

use App\Clients\HgncClient;
use App\Clients\OmimClient;
use App\Clients\MondoClient;
use App\Contracts\HgncClient as HgncClientContract;
use App\Contracts\OmimClient as OmimClientContract;
use App\Contracts\MondoClient as MondoClientContract;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(OmimClientContract::class, OmimClient::class);

        $this->app->bind(HgncClientContract::class, function () {
            $guzzleClient = new Client([
                'base_uri'=>'http://rest.genenames.org/',
                'headers'=>[
                    'Accept' => 'application/json'
                ]
            ]);
            return new HgncClient($guzzleClient);
        });

        $this->app->bind(MondoClientContract::class, function () {
            $guzzleClient = new Client([
                'base_uri' => 'https://www.ebi.ac.uk/ols/api/ontologies/mondo/',
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
            return new MondoClient($guzzleClient);
        });
    }
}
