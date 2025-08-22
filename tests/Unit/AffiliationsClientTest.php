<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Affiliation;
use App\Gci\Actions\AffiliationsUpdate;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group affiliations-client
 * @group affiliations
 * @group clients
 */
class AffiliationsClientTest extends TestCase
{
    use DatabaseTransactions;
    private $updateAction;

    public function setUp(): void
    {
        parent::setUp();
        $this->updateAction = app()->make(AffiliationsUpdate::class);
    }

    /**
     * @test
     */
    public function allows_addition_of_affiliations_with_same_name_but_different_types()
    {
        $affiliations = json_decode(file_get_contents(base_path('tests/files/affiliations-same-name-vcep-gcep.json')));
        $this->updateAction->updateAffiliationData($affiliations);
        $this->assertEquals(2, Affiliation::where('name', 'TEST1')->count());
    }


    /**
     * @test
     */
    public function allows_addition_of_parent_affiliations_with_same_name()
    {
        $affiliations = json_decode(file_get_contents(base_path('tests/files/affiliations-nonuniq-parent-name.json')));
        $this->updateAction->updateAffiliationData($affiliations);
        $this->assertEquals(2, Affiliation::where('name', 'TEST2')->count());
    }


    /**
     * @test
     */
    public function throws_exception_on_name_conflict_within_gcep()
    {
        $this->expectException(UniqueConstraintViolationException::class);
        $affiliations = json_decode(file_get_contents(base_path('tests/files/affiliations-nonuniq-gcep-name.json')));
        $this->updateAction->updateAffiliationData($affiliations);
    }

}
