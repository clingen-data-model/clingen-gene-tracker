<?php

namespace Tests\Unit\Models;

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
    public function mim_number_and_name_must_be_unique()
    {
        $phenotype = factory(Phenotype::class)->create([
            'mim_number' => 12345,
            'name' => 'test test test'
        ]);

        $this->expectException(QueryException::class);
        $phenotype2 = factory(Phenotype::class)->create([
            'mim_number' => 12345,
            'name' => 'test test test'
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
        $this->assertEquals(3, $phenotype->curations()->count());
    }

    /**
     * @test
     */
    public function omim_entry_cast_to_array()
    {
        $entry = [
            "prefix" => "#",
            "mimNumber" => 1234567,
            "status" => "live",
            "titles" => [
                "preferredTitle" => 'beans',
                "includedTitles" => 'monkeys',
            ],
        ];
        $phenotype = factory(Phenotype::class)->create(['omim_entry' => $entry]);
        $this->assertNotNull($phenotype->omim_entry);
        $this->assertIsArray($phenotype->omim_entry);
        $this->assertEquals($entry, $phenotype->omim_entry);
    }

    /**
     * @test
     */
    public function can_find_by_mim_number()
    {
        $phenotype = factory(Phenotype::class)->create([]);
        $this->assertEquals($phenotype->id, Phenotype::findByMimNumber($phenotype->mim_number)->id);

        $this->assertNull(Phenotype::findByMimNumber(666));
    }
}
