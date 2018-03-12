<?php

namespace Tests\Unit\Jobs\Topics;

use App\Jobs\Topics\SyncPhenotypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SyncPhenotypesTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->phs = factory(\App\Phenotype::class, 3)->create()->pluck('mim_number');
        $this->topic = factory(\App\Topic::class)->create();
    }

    /**
     * @test
     */
    public function adds_phenotypes_to_topic()
    {
        $job = new SyncPhenotypes($this->topic, $this->phs);
        $job->handle();

        $topic = $this->topic->fresh();

        $this->assertEquals(3, $topic->phenotypes()->count());
    }

    /**
     * @test
     */
    public function creates_new_phenotypes_and_adds_to_topic()
    {
        $newMims = collect([123456, 768910]);
        $phs = $this->phs->merge($newMims);
        $job = new SyncPhenotypes($this->topic, $phs);
        $job->handle();

        $this->assertEquals(5, $this->topic->phenotypes()->count());
        $this->assertDatabaseHas('phenotypes', ['mim_number' => 123456]);
        $this->assertDatabaseHas('phenotypes', ['mim_number' => 768910]);
    }

    /**
     * @test
     */
    public function removes_phenotypes_from_topic()
    {
        $this->phs->pop();
        $job = new SyncPhenotypes($this->topic, $this->phs);
        $job->handle();

        $this->assertEquals(2, $this->topic->phenotypes()->count());
    }
}
