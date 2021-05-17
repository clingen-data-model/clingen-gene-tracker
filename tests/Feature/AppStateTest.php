<?php

namespace Tests\Feature;

use App\AppState;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AppStateTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function accessor_casts_to_int()
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
    public function accessor_casts_to_date()
    {
        $state = $this->createState(['type' => 'date', 'value' => '2021-01-01 12:00:00']);
        $this->assertInstanceOf(Carbon::class, $state->value);
        $this->assertEquals(Carbon::parse('2021-01-01 12:00:00'), $state->value);
    }

    /**
     * @test
     */
    public function accessor_casts_json_type()
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
