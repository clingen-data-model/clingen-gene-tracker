<?php

namespace Tests\Feature;

use App\Curation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HgncInfoAddedToCurationOnSavingTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
        $this->curation = factory(Curation::class)
                            ->make([
                                'gene_symbol' => 'TP53',
                                'hgnc_id' => null,
                                'hgnc_name' => null
                            ]);
    }

    /**
     * @test
     */
    public function adds_hgnc_info_if_hgnc_id_is_null()
    {
        $this->curation->save();

        $this->assertNotNull($this->curation->hgnc_id);
        $this->assertNotNull($this->curation->hgnc_name);
    }
    
    
}
