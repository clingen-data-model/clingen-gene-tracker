<?php

namespace Tests\Unit\Jobs\Curations;

use App\Gene;
use App\Phenotype;
use Tests\TestCase;
use App\Jobs\Curations\SyncPhenotypes;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SyncPhenotypesTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->gene = factory(Gene::class)->create();
        $this->phs = factory(Phenotype::class, 3)
                        ->create();

        $this->gene->phenotypes()->sync($this->phs->pluck('id'));

        $this->phs = $this->phs->map(function ($item) {
            return [
                'mim_number' => $item->mim_number,
                'name' => $item->name
            ];
        });

        $this->curation = factory(\App\Curation::class)->create(['hgnc_id' => $this->gene->hgnc_id]);

    }

    /**
     * @test
     */
    public function adds_phenotypes_to_curation()
    {
        $job = new SyncPhenotypes($this->curation, $this->phs);
        $job->handle();

        $curation = $this->curation->fresh();

        $this->assertEquals(3, $curation->selectedPhenotypes()->count());
    }

    /**
     * @test
     */
    public function creates_new_phenotypes_and_adds_to_curation()
    {
        $newMims = collect([
            [
                'mim_number' => 123456,
                'name' => 'transsubstantiation'
            ],
            [
                'mim_number' => 768910,
                'name' => 'tetrisitis'
            ]
        ]);
        $phs = $this->phs->toBase()->merge($newMims);
        $job = new SyncPhenotypes($this->curation, $phs);
        $job->handle();

        $this->assertEquals(5, $this->curation->selectedPhenotypes()->count());
        $this->assertDatabaseHas('phenotypes', ['mim_number' => 123456, 'name' => 'transsubstantiation']);
        $this->assertDatabaseHas('phenotypes', ['mim_number' => 768910, 'name' => 'tetrisitis']);
    }

    /**
     * @test
     */
    public function removes_phenotypes_from_curation()
    {
        $job = new SyncPhenotypes($this->curation, $this->phs);
        $job->handle();

        $this->phs->pop();
        $job = new SyncPhenotypes($this->curation, $this->phs);
        $job->handle();

        $this->assertEquals(2, $this->curation->selectedPhenotypes()->count());
    }

    /**
     * @test
     */
    public function allows_multiple_phenotypes_with_the_same_mim_number_with_different_names()
    {
        $newPheno = $this->phs->first();
        $newPheno['name'] = "Bob\'s yer uncle";

        $this->phs->push($newPheno);

        $job = new SyncPhenotypes($this->curation, $this->phs);
        $job->handle();

        $this->assertEquals(4, $this->curation->selectedPhenotypes()->count());
    }

    /**
     * @test
     */
    public function adds_unselected_phenotypes_with_selected_false()
    {
        $unselectedPheno = factory(Phenotype::class)->create();
        $this->gene->addPhenotype($unselectedPheno);

        $job = new SyncPhenotypes($this->curation, $this->phs);
        $job->handle();

        $this->assertEquals(3, $this->curation->selectedPhenotypes->count());
        $this->assertEquals(1, $this->curation->excludedPhenotypes->count());
    }
    
}
