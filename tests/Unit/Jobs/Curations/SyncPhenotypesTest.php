<?php

namespace Tests\Unit\Jobs\Curations;

use App\Phenotype;
use Tests\TestCase;
use App\Jobs\Curations\SyncPhenotypes;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group phenotypes
 */
class SyncPhenotypesTest extends TestCase
{
    use DatabaseTransactions;

    private $phs, $curation;

    public function setUp(): void
    {
        parent::setUp();
        $this->phs = factory(Phenotype::class, 3)->create();
        $this->curation = factory(\App\Curation::class)->create();
    }

    /**
     * @test
     */
    public function adds_phenotypes_to_curation()
    {
        $job = new SyncPhenotypes($this->curation, $this->phs->pluck('id'));
        $job->handle();

        $curation = $this->curation->fresh();

        $this->assertEquals(3, $curation->phenotypes()->count());
    }

    /**
     * @test
     */
    public function creates_new_phenotypes_and_adds_to_curation()
    {
        
        $newMims = collect([
            factory(Phenotype::class)->create(['mim_number' => 123456]),
            factory(Phenotype::class)->create(['mim_number' => 768910]),
        ]);
        $phs = $this->phs->merge($newMims);

        $job = new SyncPhenotypes($this->curation, $phs->pluck('id'));
        $job->handle();

        $this->assertEquals(5, $this->curation->phenotypes()->count());
        $this->assertDatabaseHas('phenotypes', ['mim_number' => 123456]);
        $this->assertDatabaseHas('phenotypes', ['mim_number' => 768910]);
    }

    /**
     * @test
     */
    public function removes_phenotypes_from_curation()
    {
        $job = new SyncPhenotypes($this->curation, $this->phs->pluck('id'));
        $job->handle();

        $this->phs->pop();
        $job = new SyncPhenotypes($this->curation, $this->phs->pluck('id'));
        $job->handle();

        $this->assertEquals(2, $this->curation->phenotypes()->count());
    }
}
