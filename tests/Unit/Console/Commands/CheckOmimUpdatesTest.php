<?php

namespace Tests\Unit\Console\Commands;

use App\Curation;
use App\Phenotype;
use Tests\TestCase;
use App\Jobs\Curations\UpdateOmimData;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group omim
 */
class CheckOmimUpdatesTest extends TestCase
{
    use DatabaseTransactions;

    public function setup():void
    {
        parent::setup();
        $this->phenotype1 = factory(Phenotype::class)->create([
            'mim_number' => 614867,
            'name' => 'Peroxisome biogenesis disorder 5B'
        ]);
        $this->phenotype2 = factory(Phenotype::class)->create([
            'mim_number' => 614869,
            'name' => 'Usher syndrome, type IJ'
        ]);
        $this->phenotype3 = factory(Phenotype::class)->create([
            'mim_number' => 999999,
            'name' => 'Type Test'
        ]);

        $this->curation = factory(Curation::class)->create();
        $this->curation->phenotypes()->attach([$this->phenotype1->id, $this->phenotype2->id]);

        //setup code
    }
    

    /**
     * @test
     */
    public function dispatches_UpdateOmimData_for_each_phenotype_with_curations()
    {
        \Bus::fake();

        $this->artisan('curations:check-omim-updates')
            ->assertExitCode(0);

        \Bus::assertDispatched(UpdateOmimData::class, function ($job) {
            return $job->phenotype->id == $this->phenotype1->id;
        });

        \Bus::assertDispatched(UpdateOmimData::class, function ($job) {
            return $job->phenotype->id == $this->phenotype2->id;
        });
    }

    /**
     * @test
     */
    public function does_not_update_phenotypes_that_do_not_belong_to_curations()
    {
        \Bus::fake();

        $this->artisan('curations:check-omim-updates')
            ->assertExitCode(0);

        \Bus::assertNotDispatched(UpdateOmimData::class, function ($job) {
            return $job->phenotype->id == $this->phenotype3->id;
        });
    }
}
