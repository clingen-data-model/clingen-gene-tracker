<?php

namespace Tests\Feature\Controllers\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group omim
 * @group api
 *
 */
class OmimControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->u = factory(\App\User::class)->create();
    }
    /**
     * @test
     */
    public function gets_an_entity_from_omim()
    {
        $omimEntryResponse = json_decode(file_get_contents(base_path('tests/files/omim_api/entry_response.json')), true);
        $this->actingAs($this->u, 'api')
            ->call('GET', '/api/omim/entry?mim_number=100100')
            ->assertJson($omimEntryResponse['omim']['entryList']);
    }

    /**
     * @test
     */
    public function searches_omim_entries()
    {
        $omimEntryResponse = json_decode(file_get_contents(base_path('tests/files/omim_api/search_response.json')), true);
        $this->actingAs($this->u, 'api')
            ->call('GET', '/api/omim/search?search=myl2')
            ->assertJson($omimEntryResponse['omim']['searchResponse']['entryList']);
    }
}
