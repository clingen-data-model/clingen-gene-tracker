<?php

namespace Tests\Feature\Console\Commands;

use App\AppState;
use App\Contracts\OmimClient;
use App\Curation;
use App\ExpertPanel;
use App\Notifications\Curations\PhenotypeOmimEntryMoved;
use App\Notifications\Curations\PhenotypeOmimEntryRemoved;
use App\Phenotype;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\SeedsGenes;
use Tests\SeedsPhenotypes;
use Tests\TestCase;
use Tests\Traits\GetsOmimClient;

class UpdateOmimMovedAndRemovedTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsGenes;
    use SeedsPhenotypes;
    use GetsOmimClient;

    public function setup(): void
    {
        parent::setup();
        $this->phenotypes = $this->seedPhenotypes();
    }

    /**
     * @test
     */
    public function updates_phenotype_status_if_removed(): void
    {
        $this->bindRemoveResponse();

        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->first()->mim_number,
            'omim_status' => 'removed',
        ]);
    }

    /**
     * @test
     */
    public function updates_phenotype_status_and_moved_to_mim_number_if_moved(): void
    {
        $this->bindMovedResponse();

        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->first()->mim_number,
            'omim_status' => 'moved',
            // 'moved_to_mim_number' => json_encode(['607084'])
        ]);
        $this->assertEquals(['607084'], $this->phenotypes->first()->fresh()->moved_to_mim_number);
    }

    /**
     * @test
     */
    public function creates_movedTo_phenotype_model_if_not_found(): void
    {
        Phenotype::findByMimNumber(607084)->forceDelete();

        $this->bindMovedResponse();

        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->first()->mim_number,
            'omim_status' => 'moved',
            // 'moved_to_mim_number' => json_encode(['607084'])
        ]);

        $this->assertEquals(['607084'], $this->phenotypes->first()->fresh()->moved_to_mim_number);

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 607084,
            'omim_status' => 'live',
            'moi' => 'Autosomal recessive',
        ]);
    }

    /**
     * @test
     */
    public function coordinator_notified_when_phenotype_related_to_curation_is_removed(): void
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
    public function coordinator_notified_when_phenotype_related_to_curation_is_moved(): void
    {
        $ep = $this->makeEpAndCoordinator();
        $this->makeCurationWithPhenotype($this->phenotypes->first(), ['expert_panel_id' => $ep->id]);

        $this->bindMovedResponse();

        Notification::fake();
        $this->artisan('omim:check-moved-and-removed');

        Notification::assertSentTo($ep->coordinators->first(), PhenotypeOmimEntryMoved::class);
    }

    /**
     * @test
     */
    public function curation_phenotypes_updated_when_phenotype_related_to_curation_is_moved(): void
    {
        factory(Phenotype::class)->create([
            'mim_number' => 193510,
        ]);
        factory(Phenotype::class)->create([
            'mim_number' => 606952,
        ]);
        $ep = $this->makeEpAndCoordinator();
        $curation = $this->makeCurationWithPhenotype($this->phenotypes->last(), ['expert_panel_id' => $ep->id]);

        $this->bindMovedToMultipleResponse();

        $this->artisan('omim:check-moved-and-removed');

        $this->assertContains(193510, $curation->fresh()->phenotypes->pluck('mim_number')->toArray());
        $this->assertContains(606952, $curation->fresh()->phenotypes->pluck('mim_number')->toArray());
    }

    /**
     * @test
     */
    public function curation_phenotype_removed_when_phenotype_related_to_curation_is_removed(): void
    {
        $ep = $this->makeEpAndCoordinator();
        $curation = $this->makeCurationWithPhenotype($this->phenotypes->first(), ['expert_panel_id' => $ep->id]);

        $this->bindRemoveResponse();

        $this->artisan('omim:check-moved-and-removed');

        $this->assertNotContains(115195, $curation->fresh()->phenotypes->pluck('mim_number')->toArray());
    }

    /**
     * @test
     */
    public function updates_last_omim_moved_check_state(): void
    {
        AppState::findByName('last_omim_moved_check')->update(['value' => Carbon::yesterday()]);
        Carbon::setTestNow('2021-05-01 01:01:01');
        $this->bindMovedResponse();
        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('app_states', [
            'name' => 'last_omim_moved_check',
            'value' => '2021-05-01 01:01:01',
        ]);
    }

    /**
     * @test
     */
    public function handles_moved_to_multiple_new_mim_numbers(): void
    {
        factory(Phenotype::class)->create([
            'mim_number' => 193510,
        ]);
        factory(Phenotype::class)->create([
            'mim_number' => 606952,
        ]);

        $this->bindMovedToMultipleResponse();
        $this->artisan('omim:check-moved-and-removed');

        $movedPh = Phenotype::findByMimNumber(180200);
        $this->assertEquals(['193510', '606952'], $movedPh->moved_to_mim_number);
    }

    /**
     * @test
     */
    public function handle_all_pages_if_paginated(): void
    {
        factory(Phenotype::class)->create([
            'mim_number' => 193510,
        ]);

        $this->bindLargeResultSetResponse();
        $this->artisan('omim:check-moved-and-removed');

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->first()->mim_number,
            'omim_status' => 'moved',
        ]);
        $this->assertEquals(['607084'], $this->phenotypes->first()->fresh()->moved_to_mim_number);

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 607084,
            'omim_status' => 'live',
        ]);

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => $this->phenotypes->last()->mim_number,
            'omim_status' => 'moved',
        ]);
        $this->assertEquals(['193510'], $this->phenotypes->last()->fresh()->moved_to_mim_number);

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 193510,
            'omim_status' => 'live',
        ]);
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
            new Response(200, [], file_get_contents(base_path('tests/files/omim_api/607084_with_geneMap.json'))),
        ]);
        app()->instance(OmimClient::class, $omimClient);
    }

    private function bindMovedToMultipleResponse()
    {
        $omimClient = $this->getOmimClient([
            new Response(200, [], file_get_contents(base_path('tests/files/omim_api/moved_to_multiple_search_response.json'))),
        ]);
        app()->instance(OmimClient::class, $omimClient);
    }

    private function bindLargeResultSetResponse()
    {
        $omimClient = $this->getOmimClient([
            new Response(200, [], file_get_contents(base_path('tests/files/omim_api/large_search_response_1.json'))),
            new Response(200, [], file_get_contents(base_path('tests/files/omim_api/large_search_response_2.json'))),
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
