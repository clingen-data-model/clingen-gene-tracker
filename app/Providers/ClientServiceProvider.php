<?php

namespace App\Providers;

use App\Clients\MondoClient;
use App\Clients\OmimClient;
use App\Contracts\MondoClient as MondoClientContract;
use App\Contracts\OmimClient as OmimClientContract;
use App\Hgnc\HgncClient;
use App\Hgnc\HgncClientContract as HgncClientContract;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind(OmimClientContract::class, OmimClient::class);

        $this->app->bind(HgncClientContract::class, function () {
            $guzzleClient = new Client([
                'base_uri' => 'http://rest.genenames.org/',
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            return new HgncClient($guzzleClient);
        });

        $this->app->bind(MondoClientContract::class, function () {
            $guzzleClient = new Client([
                'base_uri' => 'https://www.ebi.ac.uk/ols/api/ontologies/mondo/',
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            return new MondoClient($guzzleClient);
        });
    }
}
