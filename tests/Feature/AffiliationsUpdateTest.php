<?php

namespace Tests\Feature;

use App\Affiliation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AffiliationsUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        // Run the seeder to populate affiliation_types table
        $this->artisan('db:seed', ['--class' => 'AffiliationTypesTableSeeder']);
    }


    /** @test */
    public function it_soft_deletes_and_renames_existing_record_when_duplicate_name_detected()
    {
        // Arrange: Create an existing affiliation with a conflicting name
        $existing = Affiliation::create([
            'clingen_id' => '12345',
            'name' => 'Male Infertility GCEP Parent',
            'affiliation_type_id' => 1,
            'deleted_at' => null,
        ]);

        // New affiliation data (same name, different clingen_id)
        $newClingenId = '12346';
        $name = 'Male Infertility GCEP Parent';

        // Act: Simulate calling the duplicate handler
        $action = app(\App\Gci\Actions\AffiliationsUpdate::class);
        $this->invokeMethod($action, 'handleDuplicateName', [$name, $newClingenId]);

        // Refresh from DB
        $existing->refresh();

        // Assert: Old record should be soft deleted and renamed
        $this->assertSoftDeleted($existing);
        $this->assertStringContainsString('(Soft Delete)', $existing->name);

        // Assert: New name still starts with original name
        $this->assertStringStartsWith($name, $existing->name);
    }

    /** @test */
    public function it_does_not_touch_existing_record_if_same_clingen_id()
    {
        // Arrange: Existing affiliation with same clingen_id
        $existing = Affiliation::create([
            'clingen_id' => '12345',
            'name' => 'Male Infertility GCEP Parent',
            'affiliation_type_id' => 1,
            'deleted_at' => null,
        ]);

        // Act: Call handler with same clingen_id
        $action = app(\App\Gci\Actions\AffiliationsUpdate::class);
        $this->invokeMethod($action, 'handleDuplicateName', ['Male Infertility GCEP Parent', '12345']);

        // Refresh from DB
        $existing->refresh();

        // Assert: Record was not soft deleted or renamed
        $this->assertNull($existing->deleted_at);
        $this->assertEquals('Male Infertility GCEP Parent', $existing->name);
    }

    /**
     * Helper to call protected/private methods.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

}
