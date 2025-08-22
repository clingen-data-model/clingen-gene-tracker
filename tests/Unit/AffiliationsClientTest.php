<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group affiliations-client
 * @group affiliations
 * @group clients
 */
class AffiliationsClientTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function allows_addition_of_affiliations_with_same_name_but_different_types()
    {
        $omimClient = app()->make(\App\Contracts\OmimClient::class);
        $this->assertInstanceOf(\App\Clients\OmimClient::class, $omimClient);
    }


    /**
     * @test
     */
    public function can_get_an_entry()
    {
        $entryJson = file_get_contents(base_path('tests/files/omim_api/entry_response.json'));
        $omim = $this->getOmimClient([ new Response(200, [], $entryJson) ]);
        $entry = $omim->getEntry(100100);

        $expectedEntry = new OmimEntry(json_decode($entryJson)->omim->entryList[0]->entry);

        $this->assertEquals($expectedEntry, $entry);
    }

    /**
     * @test
     */
    public function can_search_omim_database()
    {
        $json = file_get_contents(base_path('tests/files/omim_api/search_response.json'));
        $omim = $this->getOmimClient([new Response(200, [], $json)]);
        $results = $omim->search(['search'=>'myl2']);

        $expectedEntries = collect(json_decode($json)->omim->searchResponse->entryList)->transform(function ($entry) {
            return $entry->entry;
        });
        ;
        $this->assertEquals($expectedEntries, $results);
    }

    /**
     * @test
     */
    public function can_get_phenotypes_for_hgnc_gene_symbol()
    {
        $json = file_get_contents(base_path('tests/files/omim_api/gene_phenotypes_search.json'));
        $omim = $this->getOmimClient([new Response(200, [], $json)]);
        $results = $omim->getGenePhenotypes('TP53');

        $expectedEntries = collect(json_decode($json)->omim->searchResponse->entryList[0]->entry->geneMap->phenotypeMapList)
                                ->transform(function ($item) {
                                    return $item->phenotypeMap;
                                });
        $this->assertEquals($expectedEntries, $results);
    }

    /**
     * @test
     */
    public function determines_whether_omim_has_a_gene_symbol()
    {
        $notFoundJson = json_encode([
            'omim' => [
                'searchResponse' => [
                    'entryList' => []
                ]
            ]
        ]);
        $omim = $this->getOmimClient([new Response(200, [], $notFoundJson)]);
        $this->assertFalse($omim->geneSymbolIsValid('MLTN1'));

        $foundJson = json_encode([
            'omim' => [
                'searchResponse' => [
                    'entryList' => [
                        [
                            'entry' => [
                                'mimNumber' => '113705',
                                'titles' => [
                                    'preferredTitle' => 'BREAST CANCER 1 GENE; BRCA1'
                                ],
                                'matches' => 'brca1'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $omim = $this->getOmimClient([new Response(200, [], $foundJson)]);
        $this->assertTrue($omim->geneSymbolIsValid('BRCA1'));
    }
    
    /**
     * @test
     */
    public function caches_successful_responses_for_20_minutes()
    {
        $foundJson = (object)['test' => 'beans'];
        $omim = $this->getOmimClient([
            new Response(200, [], json_encode($foundJson)),
        ]);

        $response = $omim->fetch('test/found', []);
        $this->assertTrue(\Cache::has(sha1('test/found?')));
        $this->assertEquals($foundJson, $response);
    }
}
