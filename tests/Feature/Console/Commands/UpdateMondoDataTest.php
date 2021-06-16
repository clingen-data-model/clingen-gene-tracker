<?php

namespace Tests\Feature\Console\Commands;

use App\Disease;
use App\AppState;
use Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Tests\MocksGuzzleRequests;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group mondo
 */
class UpdateMondoDataTest extends TestCase
{
    use DatabaseTransactions;
    use MocksGuzzleRequests;

    public function setup():void
    {
        parent::setup();
        $oboData = file_get_contents(base_path('tests/files/mondo/mondo_test.obo'));
        $guzzleClient = $this->getGuzzleClient([new Response(200, [], $oboData)]);
        app()->instance(ClientInterface::class, $guzzleClient);
    }

    /**
     * @test
     */
    public function does_nothing_if_version_data_lte_mondo_date_in_app_state()
    {
        AppState::findByName('last_mondo_update')->update(['value' => '2021-06-01']);
        Artisan::call('mondo:update-data');

        $this->assertEquals(0, Disease::count());
    }

    /**
     * @test
     */
    public function imports_new_mondo_data_if_last_mondo_update_before_date_in_file()
    {
        AppState::findByName('last_mondo_update')->update(['value' => '2021-05-01']);
        Artisan::call('mondo:update-data');

        $this->assertEquals(23, Disease::count());
    }

    /**
     * @test
     */
    public function sets_last_mondo_update_app_state()
    {
        AppState::findByName('last_mondo_update')->update(['value' => '2021-05-01']);
        Artisan::call('mondo:update-data');

        $this->assertDatabaseHas('app_states', [
            'name' => 'last_mondo_update',
            'value' => '2021-06-01 00:00:00'
        ]);
    }
}
