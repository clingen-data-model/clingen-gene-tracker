<?php

namespace Tests\Unit\Events\Curation;

use App\Curation;
use App\Phenotype;
use Tests\TestCase;
use App\Events\RecordableEvent;
use App\Events\Curation\CurationPhenotypesUpdated;
use Carbon\Carbon;

/**
 * @group phenotypes
 */
class CurationPhenotypesUpdatedTest extends TestCase
{
    private Curation $curation;
    private CurationPhenotypesUpdated $event;
    private $phs;

    public function setup(): void
    {
        parent::setup();
        $this->phs = factory(Phenotype::class, 3)->create();
        $this->curation = factory(Curation::class)->create();

        // update add the seeded phenotypes to the curation
        $this->curation->phenotypes()->sync($this->phs->pluck('id'));

        $this->event = new CurationPhenotypesUpdated($this->curation, [1,2,4]);
    }

    /**
     * @test
     */
    public function it_is_a_recordable_event():void
    {
        $this->assertInstanceOf(RecordableEvent::class, $this->event);
    }
    

    /**
     * @test
     */
    public function it_gets_the_correct_log():void
    {
        $this->assertEquals('curations', $this->event->getLog());
    }
    
    /**
     * @test
     */
    public function has_subject_returns_true():void
    {
        $this->assertTrue($this->event->hasSubject());
    }
    
    /**
     * @test
     */
    public function get_subject_returns_the_curation():void
    {
        $subject = $this->event->getSubject();
        $this->assertInstanceOf(Curation::class, $subject);
        $this->assertEquals($this->curation->id, $subject->id);
    }
    
    /**
     * @test
     */
    public function get_properties_returns_the_old_and_new_phenotype_ids():void
    {
        $this->assertEquals([
            'old' => [1,2,4],
            'new' => $this->phs->pluck('id')->toArray(),
        ], $this->event->getProperties());
    }
    
    /**
     * @test
     */
    public function returns_the_correct_log_entry():void
    {
        $this->assertEquals('Phenotypes updated for curation '.$this->curation->id, $this->event->getLogEntry());
    }
    
    /**
     * @test
     */
    public function returns_now_as_the_log_date():void
    {
        Carbon::withTestNow('2024-01-01', function () {
            $this->assertEquals(Carbon::now(), $this->event->getLogDate());            
        });
    }
}

