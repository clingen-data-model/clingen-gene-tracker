<?php

namespace Tests\Unit\Jobs\Curations;

use App\Curation;
use Tests\TestCase;
use App\Classification;
use App\Jobs\Curations\AddClassification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group classifications
 */
class AddClassificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function adds_a_classification_to_a_curation()
    {
        \Event::fake();
        $classification = factory(Classification::class)->create([]);
        $curation = factory(Curation::class)->create();
        $job = new AddClassification($curation, $classification, '2019-12-25');
        $job->handle();

        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $curation->id,
            'classification_id' => $classification->id,
            'classification_date' => '2019-12-25'
        ]);
    }
    
}
