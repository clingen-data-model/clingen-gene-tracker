<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidOmimId;
use Tests\TestCase;

class ValidOmimIdTest extends TestCase
{
    /**
     * @test
     */
    public function fails_when_not_a_valid_omim_id()
    {
        $rule = new ValidOmimId();

        $this->assertFalse($rule->passes('isolated_phenotype', '123456'));
    }

    /**
     * @test
     */
    public function passes_when_valid_omim_id()
    {
        $rule = new ValidOmimId();

        $this->assertTrue($rule->passes('isolated_phenotype', '608679'));
    }
}
