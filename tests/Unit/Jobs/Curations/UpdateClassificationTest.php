<?php

namespace Tests\Unit\Jobs\Curations;

use App\Classification;
use App\Curation;
use App\Jobs\Curations\UpdateClassification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group classifications
 */
class UpdateClassificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function updates_a_classification_associated_with_a_curation(): void
    {
        $classification = factory(Classification::class)->create();
        $curation = factory(Curation::class)->create();
        $curation->classifications()->attach([$classification->id => ['classification_date' => '2019-12-25']]);

        $job = new UpdateClassification($curation, $curation->classifications()->first()->pivot->id, [
            'classification_id' => $classification->id,
            'classification_date' => '2020-01-01',
        ]);
        $job->handle();

        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $curation->id,
            'classification_id' => $classification->id,
            'classification_date' => '2020-01-01',
        ]);
    }
}
