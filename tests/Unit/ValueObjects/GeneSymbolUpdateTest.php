<?php

namespace Tests\Unit\ValueObjects;

use App\Contracts\GeneSymbolUpdate as AppGeneSymbolUpdate;
use Tests\TestCase;
use App\ValueObjects\GeneSymbolUpdate;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeneSymbolUpdateTest extends TestCase
{
    /**
     * @test
     */
    public function can_be_instantiated_and_implements_interface()
    {
        $geneSymbolUpdateObj = new GeneSymbolUpdate('a', 'b');
        $this->assertInstanceOf(AppGeneSymbolUpdate::class, $geneSymbolUpdateObj);
    }

    /**
     * @test
     */
    public function knows_if_the_symbol_was_found()
    {
        $obj = new GeneSymbolUpdate('a', null);
        $this->assertFalse($obj->wasFound());

        $obj = new GeneSymbolUpdate('a', 'b');
        $this->assertTrue($obj->wasFound());
    }

    /**
     * @test
     */
    public function knows_if_symbol_was_updated()
    {
        $obj = new GeneSymbolUpdate('a', 'a');
        $this->assertFalse($obj->wasUpdated());

        $obj = new GeneSymbolUpdate('a', 'b');
        $this->assertTrue($obj->wasUpdated());
    }
}
