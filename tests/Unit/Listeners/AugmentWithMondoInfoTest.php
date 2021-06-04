<?php

namespace Tests\Unit\Listeners;

use App\Curation;
use Tests\TestCase;
use App\Events\Curation\Saved;
use App\Jobs\Curations\AugmentWithMondoInfo as MondoJob;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Listeners\Curations\AugmentWithMondoInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AugmentWithMondoInfoTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp():void
    {
        parent::setUp();
        $this->curation = factory(Curation::class)->create();
        // make gene_symbol and mondo_id dirty so conditions met
        $this->curation->gene_symbol = 'MLTN1';
        $this->curation->mondo_id = '00012345';
        $this->event = new Saved($this->curation);
        $this->listener = new AugmentWithMondoInfo();
    }
    

    /**
     * @test
     */
    public function dispatches_AugmentWithMondoInfo()
    {
        \Bus::fake();
        $this->listener->handle($this->event);

        \Bus::assertDispatched(MondoJob::class);
    }

    /**
     * @test
     */
    public function does_not_dispatch_AugmentWithMondoInfo_if_mondo_id_not_dirty()
    {
        $this->curation->save();

        \Bus::fake();
        $this->listener->handle($this->event);
        \Bus::assertNotDispatched(MondoJob::class);
    }
}