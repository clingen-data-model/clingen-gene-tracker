<?php

namespace Tests\Unit\Jobs\Curations;

use App\Jobs\Curations\SyncPhenotypes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SyncPhenotypesTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->phs = factory(\App\Phenotype::class, 3)->create()->pluck('mim_number');
        $this->curation = factory(\App\Curation::class)->create();
    }

    /**
     * @test
     */
    public function adds_phenotypes_to_curation()
    {
        $job = new SyncPhenotypes($this->curation, $this->phs);
        $job->handle();

        $curation = $this->curation->fresh();

        $this->assertEquals(3, $curation->phenotypes()->count());
    }

    /**
     * @test
     */
    public function creates_new_phenotypes_and_adds_to_curation()
    {
        $newMims = collect([123456, 768910]);
        $phs = $this->phs->merge($newMims);
        $job = new SyncPhenotypes($this->curation, $phs);
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
        $this->phs->pop();
        $job = new SyncPhenotypes($this->curation, $this->phs);
        $job->handle();

        $this->assertEquals(2, $this->curation->phenotypes()->count());
    }
}
