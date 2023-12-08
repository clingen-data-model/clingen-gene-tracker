<?php

namespace Tests\Feature;

use App\Contracts\SearchService;
use App\Curation;
use App\Disease;
use App\ExpertPanel;
use App\Phenotype;
use App\Services\Curations\CurationSearchService;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group search
 * @group curations
 */
class CurationSearchTest extends TestCase
{
    public function setup(): void
    {
        parent::setup();
        $this->user = factory(\App\User::class)->create();
        $this->curations = factory(\App\Curation::class, 10)->create(['curator_id' => $this->user->id]);
        $this->panel = factory(\App\ExpertPanel::class)->create();
        $this->rationale = factory(\App\Rationale::class)->create();
        $this->curationType = factory(\App\CurationType::class)->create();

        $this->search = new CurationSearchService();
    }

    /**
     * @test
     */
    public function implements_SearchService_interface(): void
    {
        $this->assertInstanceOf(SearchService::class, $this->search);
    }

    /**
     * @test
     */
    public function index_lists_curations_filtered_by_gene_symbol(): void
    {
        $this->withoutExceptionHandling();
        $testGene = 'BRCA1';
        $curation = factory(\App\Curation::class, 4)->create(['gene_symbol' => $testGene]);
        $curation = factory(\App\Curation::class, 3)->create([]);

        $results = $this->search->search(['gene_symbol' => $testGene]);

        $this->assertEquals(4, $results->count());
    }

    /**
     * @test
     */
    public function can_filter_results_by_mondo_id(): void
    {
        factory(Disease::class)->create(['mondo_id' => 'MONDO:12345']);
        factory(Disease::class)->create(['mondo_id' => 'MONDO:98765']);
        $curation1 = $this->curations->shift();
        $curation1->update([
            'mondo_id' => 'MONDO:12345',
        ]);
        $curation2 = $this->curations->shift();
        $curation2->update([
            'mondo_id' => 'MONDO:98765',
        ]);

        $results = $this->search->search(['mondo_id' => 'MONDO:12345']);
        $this->assertContains($curation1->mondo_id, $results->pluck('mondo_id'));
        $this->assertNotContains($curation2->mondo_id, $results->pluck('mondo_id'));

        $results = $this->search->search(['filter' => 'MONDO:12345']);
        $this->assertContains($curation1->mondo_id, $results->pluck('mondo_id'));
        $this->assertNotContains($curation2->mondo_id, $results->pluck('mondo_id'));
    }

    /**
     * @test
     */
    public function can_filter_results_by_hgnc_id(): void
    {
        $curation1 = $this->curations->shift();
        $curation1->update(['hgnc_id' => '12345']);

        $curation2 = $this->curations->shift();
        $curation2->update(['hgnc_id' => '98765']);

        $results = $this->search->search(['filter' => '12345']);
        $this->assertContains((int) $curation1->hgnc_id, $results->pluck('hgnc_id')->toArray());
        $this->assertNotContains($curation2->hgnc_id, $results->pluck('hgnc_id'));
    }

    /**
     * @test
     */
    public function can_filter_results_by_phenotype_mim_number(): void
    {
        [$ph1, $ph2, $ph3] = factory(Phenotype::class, 3)->create([]);
        [$curation1, $curation2, $restOfCurations] = $this->curations;
        $curation1->phenotypes()->sync([$ph1->id, $ph2->id]);
        $curation2->phenotypes()->sync($ph3->id);

        $results = $this->search->search(['filter' => $ph1->mim_number]);
        $this->assertContains($curation1->id, $results->pluck('id'));
        $this->assertNotContains($curation2->id, $results->pluck('id'));
    }

    /**
     * @test
     */
    public function can_eager_load_specified_relations(): void
    {
        $user = factory(User::class)->create();
        $this->curations->first()->expertPanel->addCoordinator($user);

        $results = $this->search->search(['with' => ['expertPanel', 'expertPanel.coordinators']]);

        $this->assertNotEquals(0, $results->pluck('expertPanel.coordinators')->flatten()->count());
    }

    /**
     * @test
     */
    public function can_filter_by_specific_field(): void
    {
        DB::table('curations')->delete();
        $curation = factory(Curation::class)->create(['gene_symbol' => 'RETT']);
        $ep = factory(ExpertPanel::class)->create(['name' => 'retina']);
        $epCuration = factory(Curation::class)->create(['expert_panel_id' => $ep->id]);

        $results = $this->search->search(['filter' => 'ret', 'filter_field' => 'gene_symbol']);

        $this->assertEquals(1, $results->count());
    }
}
