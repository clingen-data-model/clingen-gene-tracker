<?php

namespace Tests\Unit\Jobs\Curation;

use App\User;
use App\Curation;
use Tests\TestCase;
use App\ExpertPanel;
use App\MondoRecord;
use App\Contracts\MondoClient;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curations\AugmentWithMondoInfo;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Curations\MondoIdNotFound;
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
        $this->ep = factory(ExpertPanel::class)->create();
        $this->user = factory(User::class)->create();
        $this->user->expertPanels()->attach([$this->ep->id => ['is_coordinator' => true]]);
        $this->curation = factory(Curation::class)->create([
            'gene_symbol' => 'TH',
            'mondo_id' => 'MONDO:0043494',
            'expert_panel_id' => $this->ep->id
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
    public function fails_silently_if_mondo_id_not_found()
    {
        $this->curation->mondo_id = 'mondo:0000000';
        $this->mondoClient->method('fetchRecord')
                        ->will($this->throwException(new HttpNotFoundException()));

        app()->instance(MondoClient::class, $this->mondoClient);

        $job = new AugmentWithMondoInfo($this->curation);

        $job->handle($this->mondoClient);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @group mail
     * @group notifications
     */
    public function sends_MondoIdNotFound_to_coordinator_if_mondo_id_not_found()
    {
        $this->curation->mondo_id = 'mondo:0000000';
        $this->mondoClient->method('fetchRecord')
                        ->will($this->throwException(new HttpNotFoundException()));

        app()->instance(MondoClient::class, $this->mondoClient);

        $job = new AugmentWithMondoInfo($this->curation);

        Notification::fake();
        try {
            $job->handle($this->mondoClient);
        } catch (\Throwable $th) {
            //throw $th;
        }

        Notification::assertSentTo($this->user, MondoIdNotFound::class);
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
