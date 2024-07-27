<?php

namespace Tests\Unit\Jobs\Curations;

use App\Curation;
use App\Events\Curation\CurationPhenotypesUpdated;
use App\Phenotype;
use Tests\TestCase;
use App\Jobs\Curations\SyncPhenotypes;
use Event;
use Illuminate\Validation\ValidationException;
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
        $this->curation = factory(Curation::class)->create();
    }

    /**
     * @test
     */
    public function adds_phenotypes_to_curation()
    {
        $job = new SyncPhenotypes($this->curation, $this->phs->pluck('id')->toArray());
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

        $job = new SyncPhenotypes($this->curation, $phs->pluck('id')->toArray());
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
        $job = new SyncPhenotypes($this->curation, $this->phs->pluck('id')->toArray());
        $job->handle();

        $this->phs->pop();
        $job = new SyncPhenotypes($this->curation, $this->phs->pluck('id')->toArray());
        $job->handle();

        $this->assertEquals(2, $this->curation->phenotypes()->count());
    }

    /**
     * @test
     */
    public function it_throws_a_validation_exception_when_a_phenotype_id_is_not_found():void
    {
        $this->expectException(ValidationException::class);

        (new SyncPhenotypes(
            $this->curation, 
            $this->phs->pluck('id')->push(666)->toArray()
        ))->handle();
    }
    
    /**
     * @test
     */
    public function when_phenotypes_changed_it_fires_curation_phenotypes_updated_event():void
    {
        $this->curation->phenotypes()->sync($this->phs->pluck('id')->toArray());

        Event::fake();

        (new SyncPhenotypes($this->curation->fresh(), [$this->phs->first()->id]))->handle();

        Event::assertDispatched(function (CurationPhenotypesUpdated $event) {
            return $event->curation->id == $this->curation->id
                && $event->previousPhenotypeIds->toArray() == $this->phs->pluck('id')->toArray();
        });
    }
    
    /**
     * @test
     */
    public function when_phenotypes_unchanged_it_does_not_fire_curation_phenotypes_updated_event():void
    {
        $this->curation->phenotypes()->sync($this->phs->pluck('id')->toArray());

        Event::fake();

        (new SyncPhenotypes($this->curation->fresh(), $this->phs->pluck('id')->toArray()))->handle();

        Event::assertNotDispatched(CurationPhenotypesUpdated::class);
    }
    
}
