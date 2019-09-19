<?php

namespace App\Providers;

use App\Clients\HgncClient;
use App\Clients\OmimClient;
use App\Contracts\HgncClient as HgncClientContract;
use App\Contracts\OmimClient as OmimClientContract;
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
    }
}
