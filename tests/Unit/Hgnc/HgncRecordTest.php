<?php

namespace Tests\Unit\Hgnc;

use App\Hgnc\HgncRecord;
use Tests\TestCase;

class HgncRecordTest extends TestCase
{
    /**
     * @test
     */
    public function gets_numeric_hgnc_id()
    {
        $prevSymbolResponse = json_decode(file_get_contents(base_path('tests/files/hgnc_api/prev_symbol.json')));
        $hgncRecord = new HgncRecord($prevSymbolResponse->response->docs[0]);

        $this->assertEquals(4036, $hgncRecord->hgnc_id);
    }
    

    /**
     * @test
     */
    public function knows_wether_there_was_a_previous_symbol()
    {
        $prevSymbolResponse = json_decode(file_get_contents(base_path('tests/files/hgnc_api/prev_symbol.json')));
        $hgncRecord = new HgncRecord($prevSymbolResponse->response->docs[0]);

        $this->assertTrue($hgncRecord->hasPreviousSymbol());
    }
}
