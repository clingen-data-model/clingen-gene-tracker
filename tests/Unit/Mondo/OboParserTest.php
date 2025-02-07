<?php

namespace Tests\Unit\Mondo;

use Tests\TestCase;
use App\Mondo\OboParser;

/**
 * @group mondo
 */
class OboParserTest extends TestCase
{
    protected $parser;
    
    public function setup():void
    {
        parent::setup();
        $this->parser = new OboParser(base_path('tests/files/mondo/mondo_test.obo'));
    }
    

    /**
     * @test
     */
    public function it_gets_the_date_of_the_version()
    {
        $this->assertEquals('2021-06-01', $this->parser->getVersionDate());
    }

    /**
     * @test
     */
    public function getNextTerm_gets_next_term()
    {
        $testTerm1 = [
            'mondo_id' => 'MONDO:0000001',
            'doid_id' => 'DOID:4',
            'name' => 'disease or disorder',
            "is_obsolete" => false,
            "replaced_by" => null
        ];

        $testTerm2 = [
            'mondo_id' => 'MONDO:0600008',
            'name' => 'cytokine release syndrome',
            "is_obsolete" => false,
            "replaced_by" => null
        ];

        $testTerm3 = [
            'mondo_id' => 'MONDO:0000002',
            'name' => 'obsolete 46,XX sex reversal',
            'is_obsolete' => true,
            'replaced_by' => 'MONDO:0009299'
        ];

        $testTerm4 = [
            'mondo_id' => 'MONDO:0000224',
            'name' => 'acquired carbohydrate metabolism disease',
            'is_obsolete' => false,
            'replaced_by' => null,
        ];

        $term1 = $this->parser->getNextTerm();
        $this->assertEquals($testTerm1, $term1);

        $term2 = $this->parser->getNextTerm();
        $this->assertEquals($testTerm2, $term2);

        $term3 = $this->parser->getNextTerm();
        $this->assertEquals($testTerm3, $term3);

        $term4 = $this->parser->getNextTerm();
        $this->assertEquals($testTerm4, $term4);
    }
}
