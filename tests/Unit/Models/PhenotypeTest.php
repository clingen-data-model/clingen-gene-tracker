<?php

namespace Tests\Unit\models;

use App\Curation;
use App\Phenotype;
use Tests\TestCase;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    public function phenotype_has_fillable_mim_number()
    {
        $phenotype = factory(Phenotype::class)->create();
        $phenotype->update(['mim_number' => 1234]);

        $this->assertNotNull($phenotype->mim_number);
    }

    /**
     * @test
     */
    public function phenotype_has_name()
    {
        $phenotype = factory(Phenotype::class)->create();
        $phenotype->update(['name' => 'bobism']);

        $this->assertNotNull($phenotype->name);
    }
    
    /**
     * @test
     */
    public function can_create_new_phenotype()
    {
        $phenotype = factory(Phenotype::class)->create([
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
        $phenotype = factory(Phenotype::class)->create([
            'mim_number' => 12345
        ]);

        $this->expectException(QueryException::class);
        $phenotype2 = factory(Phenotype::class)->create([
            'mim_number' => 12345
        ]);
    }

    /**
     * @test
     */
    public function phenotype_has_many_curations_relationship()
    {
        $phenotype = factory(Phenotype::class)->create();
        $curations = factory(Curation::class, 3)->create();
        $phenotype->curations()->attach($curations->pluck('id'));

        $this->assertInstanceOf(BelongsToMany::class, $phenotype->curations());
        $this->assertEquals(3, $phenotype->curations->count());
    }
}
