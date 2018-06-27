<?php

namespace Tests\Unit\models;

use App\Phenotype;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group phenotypes
 * @group models
 */
class PhenotypeTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function phenotype_model_exists()
    {
        $phenotype = new Phenotype();
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function phenotype_has_mim_number()
    {
        $phenotype = new Phenotype();
        $phenotype->mim_number = 1234;
        $phenotype->save();

        $this->assertNotNull($phenotype->mim_number);
    }

    /**
     * @test
     */
    public function can_create_new_phenotype()
    {
        $phenotype = factory(\App\Phenotype::class)->create([
            'mim_number' => 12345
        ]);

        $allPhenes = Phenotype::all();
        $this->assertEquals(1, $allPhenes->count());
    }

    /**
     * @test
     */
    public function mim_number_must_be_unique()
    {
        $phenotype = factory(\App\Phenotype::class)->create([
            'mim_number' => 12345
        ]);

        $this->expectException(QueryException::class);
        $phenotype2 = factory(\App\Phenotype::class)->create([
            'mim_number' => 12345
        ]);
    }

    /**
     * @test
     */
    public function phenotype_has_many_curations_relationship()
    {
        $phenotype = factory(\App\Phenotype::class)->create();
        $curations = factory(\App\Curation::class, 3)->create();
        $phenotype->curations()->attach($curations->pluck('id'));

        $this->assertInstanceOf(BelongsToMany::class, $phenotype->curations());
        $this->assertEquals(3, $phenotype->curations->count());
    }
}
