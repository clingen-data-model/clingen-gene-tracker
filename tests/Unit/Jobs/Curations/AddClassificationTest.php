<?php

namespace Tests\Unit\Jobs\Curations;

use App\Curation;
use Tests\TestCase;
use App\Classification;
use App\CurationClassification;
use App\Jobs\Curations\AddClassification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group classifications
 * @group curations
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

    /**
     * @test
     */
    public function does_not_add_current_classification_again()
    {
        \Event::fake();
        $classification = factory(Classification::class)->create([]);
        $curation = factory(Curation::class)->create();
        $job = new AddClassification($curation, $classification, '2019-12-25');
        $job->handle();

        $curation = $curation->fresh();
        
        $job2 = new AddClassification($curation, $classification, '2020-12-25');
        $job2->handle();

        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $curation->id,
            'classification_id' => $classification->id,
            'classification_date' => '2019-12-25'
        ]);

        $this->assertDatabaseMissing('classification_curation', [
            'curation_id' => $curation->id,
            'classification_id' => $classification->id,
            'classification_date' => '2020-12-25'
        ]);
    }

    /**
     * @test
     */
    public function does_not_add_previously_added_classification_if_date_matches_existing_classification_date()
    {
        \Event::fake();
        $classification = factory(Classification::class)->create([]);
        $curation = factory(Curation::class)->create();
        $job = new AddClassification($curation, $classification, '2020-02-25');
        $job->handle();

        AddClassification::dispatchSync(
            $curation->fresh(), 
            Classification::find(config('project.classifications.moderate')),
            '2019-12-01'
        );

        AddClassification::dispatchSync(
            $curation->fresh(), 
            Classification::find(config('project.classifications.moderate')),
            '2019-12-01'
        );

        $this->assertEquals(2, $curation->fresh()->classifications()->count());

    }

}
