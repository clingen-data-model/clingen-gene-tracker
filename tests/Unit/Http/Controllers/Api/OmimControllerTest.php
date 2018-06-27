<?php

namespace Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group omim
 * @group omim-controller
 * @group api
 *
 */
class OmimControllerTest extends TestCase
{
    use DatabaseTransactions;
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
            ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][0]['entry'])
            ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][1]['entry'])
            ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][2]['entry'])
            ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][3]['entry'])
            ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][4]['entry'])
            ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][5]['entry'])
            // ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][6]['entry'])
            // ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][8]['entry'])
            // ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][9]['entry'])
            ;
    }
}
