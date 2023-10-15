<?php

namespace Tests\Feature;

use App\AppState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AppStateTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function accessor_casts_to_int(): void
    {
        $state = $this->createState(['type' => 'int', 'value' => '21']);
        $this->assertIsInt($state->value);
        $this->assertEquals(21, $state->value);

        $state = $this->createState(['type' => 'integer', 'value' => '21']);
        $this->assertIsInt($state->value);
        $this->assertEquals(21, $state->value);
    }

    /**
     * @test
     */
    public function accessor_casts_to_date(): void
    {
        $state = $this->createState(['type' => 'date', 'value' => '2021-01-01 12:00:00']);
        $this->assertInstanceOf(Carbon::class, $state->value);
        $this->assertEquals(Carbon::parse('2021-01-01 12:00:00'), $state->value);
    }

    /**
     * @test
     */
    public function accessor_casts_json_type(): void
    {
        $state = $this->createState(['type' => 'json', 'value' => '["a", "b", "c"]']);
        $this->assertIsArray($state->value);
        $state = $this->createState(['type' => 'json', 'value' => '{"a":"a", "b":"b", "c":"c"}']);
        $this->assertIsArray($state->value);
    }

    private function createState($data)
    {
        return factory(AppState::class)->create($data);
    }
}
