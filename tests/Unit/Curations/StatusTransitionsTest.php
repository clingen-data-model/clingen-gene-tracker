<?php

namespace Tests\Unit\Curations;

use App\Curations\StatusTransitions;
use Tests\TestCase;

/**
 * @group curations
 * @group curation-history
 */
#[\PHPUnit\Framework\Attributes\Group('curations')]
#[\PHPUnit\Framework\Attributes\Group('curation-history')]
class StatusTransitionsTest extends TestCase
{
    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function allows_each_step_along_the_workflow()
    {
        foreach ([[1, 2], [2, 3], [3, 4], [4, 5], [5, 6], [6, 7], [6, 9], [9, 7]] as [$from, $to]) {
            $this->assertTrue(
                StatusTransitions::isAllowed($from, $to),
                "expected {$from} -> {$to} to be allowed"
            );
        }
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function allows_retirement_from_any_state()
    {
        foreach (range(1, 9) as $from) {
            $this->assertTrue(StatusTransitions::isAllowed($from, StatusTransitions::TERMINAL));
        }
    }

    /**
     * The transitions the history is full of but the graph does not contain. If any
     * of these becomes allowed, that is a deliberate change to the state machine.
     *
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function rejects_transitions_outside_the_graph()
    {
        foreach ([[6, 5], [5, 9], [9, 5], [7, 5], [2, 1], [5, 4], [1, 5]] as [$from, $to]) {
            $this->assertFalse(
                StatusTransitions::isAllowed($from, $to),
                "expected {$from} -> {$to} to be outside the graph"
            );
        }
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_successors()
    {
        $this->assertEquals([7, 9], StatusTransitions::successorsOf(6));
        $this->assertEquals([], StatusTransitions::successorsOf(StatusTransitions::TERMINAL));
    }
}
