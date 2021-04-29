<?php

namespace Tests\Feature\Console\Commands;

use App\User;
use App\Curation;
use App\Phenotype;
use Tests\TestCase;
use App\ExpertPanel;
use Tests\SeedsGenes;
use Tests\SeedsPhenotypes;
use App\Contracts\OmimClient;
use GuzzleHttp\Psr7\Response;
use Tests\MocksGuzzleRequests;
use Tests\Traits\GetsOmimClient;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Notifications\Curations\PhenotypeOmimEntryMoved;
use App\Notifications\Curations\PhenotypeOmimEntryRemoved;

class UpdateOmimMovedAndRemovedTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsGenes;
    use SeedsPhenotypes;
    use GetsOmimClient;

    public function setup():void
    {
        parent::setup();
        $this->phenotypes = $this->seedPhenotypes();
    }

    /**
     * @test
     */
    public function updates_phenotype_status_if_removed()
    {
        $this->bindRemoveResponse();

        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->first()->mim_number,
            'omim_status' => 'removed'
        ]);
    }

    /**
     * @test
     */
    public function updates_phenotype_status_and_moved_to_mim_number_if_moved()
    {
        $this->bindMovedResponse();
        
        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->first()->mim_number,
            'omim_status' => 'moved',
            'moved_to_mim_number' => 139139
        ]);
    }

    /**
     * @test
     */
    public function creates_movedTo_phenotype_model_if_not_found()
    {
        Phenotype::findByMimNumber(139139)->forceDelete();

        $this->bindMovedResponse();
        
        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->first()->mim_number,
            'omim_status' => 'moved',
            'moved_to_mim_number' => 139139
        ]);

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 139139,
            'omim_status' => 'live'
        ]);
    }

    /**
     * @test
     */
    public function coordinator_notified_when_phenotype_related_to_curation_is_removed()
    {
        $ep = $this->makeEpAndCoordinator();
        $this->makeCurationWithPhenotype($this->phenotypes->first(), ['expert_panel_id' => $ep->id]);

        $this->bindRemoveResponse();

        Notification::fake();
        $this->artisan('omim:check-moved-and-removed');

        Notification::assertSentTo($ep->coordinators->first(), PhenotypeOmimEntryRemoved::class);
    }

    /**
     * @test
     */
    public function coordinator_notified_when_phenotype_related_to_curation_is_moved()
    {
        $ep = $this->makeEpAndCoordinator();
        $this->makeCurationWithPhenotype($this->phenotypes->first(), ['expert_panel_id' => $ep->id]);

        $this->bindMovedResponse();

        Notification::fake();
        $this->artisan('omim:check-moved-and-removed');

        Notification::assertSentTo($ep->coordinators->first(), PhenotypeOmimEntryMoved::class);
    }

    private function bindRemoveResponse()
    {
        $omimClient = $this->getOmimClient([new Response(200, [], file_get_contents(base_path('tests/files/omim_api/entry_removed_search_response.json')))]);
        app()->instance(OmimClient::class, $omimClient);
    }

    private function bindMovedResponse()
    {
        $omimClient = $this->getOmimClient([
            new Response(200, [], file_get_contents(base_path('tests/files/omim_api/entry_moved_search_response.json'))),
            new Response(200, [], file_get_contents(base_path('tests/files/omim_api/139139.json')))
        ]);
        app()->instance(OmimClient::class, $omimClient);
    }

    private function makeEpAndCoordinator()
    {
        $ep = factory(ExpertPanel::class)->create();
        $user = factory(User::class)->create();
        $ep->addCoordinator($user);

        return $ep;
    }

    private function makeCurationWithPhenotype($phenotype, $curationAttr = [])
    {
        $curation = factory(Curation::class)->create($curationAttr);
        $curation->addPhenotype($phenotype);
        return $curation;
    }
}
