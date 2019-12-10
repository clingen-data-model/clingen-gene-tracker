<?php

namespace Tests\Unit\Console\Commands;

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

    /**
     * @test
     */
    public function dispatches_UpdateOmimData_for_each_phenotype()
    {
        $phenotype1 = factory(Phenotype::class)->create([
            'mim_number' => 614867,
            'name' => 'Peroxisome biogenesis disorder 5B'
        ]);
        $phenotype2 = factory(Phenotype::class)->create([
            'mim_number' => 614869,
            'name' => 'Usher syndrome, type IJ'
        ]);

        \Bus::fake();

        $this->artisan('curations:check-omim-updates')
            ->assertExitCode(0);

        \Bus::assertDispatched(UpdateOmimData::class, function ($job) use ($phenotype1) {
            return $job->phenotype->id == $phenotype1->id;
        }); 

        \Bus::assertDispatched(UpdateOmimData::class, function ($job) use ($phenotype2) {
            return $job->phenotype->id == $phenotype2->id;
        }); 
    }
    
}
