<?php

namespace Tests\Unit\Jobs\Curation;

use App\Curation;
use Tests\TestCase;
use App\Contracts\MondoClient;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curation\AugmentWithMondoInfo;
use App\MondoRecord;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group clients
 * @group mondo
 */
class AugmentWithMondoInfoTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp():void
    {
        parent::setUp();
        $this->curation = factory(Curation::class)->create([
            'gene_symbol' => 'TH',
            'mondo_id' => 'MONDO:0043494'
        ]);
        $this->mondoClient = $this->getMockBuilder(MondoClient::class)
                                ->getMock();

        $this->mondoClient->method('fetchRecord')
                        ->willReturn(new MondoRecord((object)[
                            "iri" => "http://purl.obolibrary.org/obo/MONDO_0043494",
                            "label" => "arteritis",
                            "description" => null,
                        ]));
    }

    /**
     * @test
     */
    public function throws_HttpNotFoundException_if_mondo_id_not_found()
    {
        $this->curation->mondo_id = 'mondo:0000000';
        $this->mondoClient->method('fetchRecord')
                        ->will($this->throwException(new HttpNotFoundException()));

        app()->instance(MondoClient::class, $this->mondoClient);

        $job = new AugmentWithMondoInfo($this->curation);

        $this->expectException(HttpNotFoundException::class);
        $job->handle($this->mondoClient);        
    }

     /**
     * @test
     */
    public function adds_mondo_name_to_curation()
    {
        $job = new AugmentWithMondoInfo($this->curation);

        $job->handle($this->mondoClient);

        $this->assertDatabaseHas('curations', ['gene_symbol' => 'TH', 'mondo_name' => 'arteritis']);
    }
   
}
