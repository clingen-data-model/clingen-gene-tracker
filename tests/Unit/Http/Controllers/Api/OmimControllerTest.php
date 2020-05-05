<?php

namespace Tests\Unit\Http\Controllers\Api;

use Tests\TestCase;
use App\Clients\Omim\OmimEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group omim
 * @group omim-controller
 * @group api
 *
 */
class OmimControllerTest extends TestCase
{
    use DatabaseTransactions;
    public function setUp(): void
    {
        parent::setUp();
        $this->u = factory(\App\User::class)->create();
    }
    /**
     * @test
     */
    public function gets_an_entity_from_omim()
    {
        $omimEntryResponse = json_decode(file_get_contents(base_path('tests/files/omim_api/entry_response.json')));
        $entry = new OmimEntry($omimEntryResponse->omim->entryList[0]->entry);
        $response = $this->actingAs($this->u, 'api')
            ->call('GET', '/api/omim/entry?mim_number=100100');
        $response->assertJson($entry->toArray());
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
            ->assertJsonFragment($omimEntryResponse['omim']['searchResponse']['entryList'][5]['entry']);
    }

    /**
     * @test
     */
    public function checks_to_see_if_gene_symbol_is_valid()
    {
        $this->actingAs($this->u, 'api')
            ->call('GET', '/api/omim/gene/MLTN1')
            ->assertStatus(404)
            ->assertSee('No HGNC gene symbol was found for MLTN1');

        $this->actingAs($this->u, 'api')
            ->call('GET', '/api/omim/gene/BRCA1')
            ->assertStatus(200);
    }
}
