<?php

namespace App\Curations;

/**
 * The curation status state machine.
 *
 * This has never been expressed in code -- statuses were written and ordered
 * without anything checking that one could follow another -- so it is recorded
 * here as data, and curations:audit-status-transitions measures the history
 * against it. It is deliberately the graph as specified, not the graph the data
 * implies: the gap between them is the finding.
 */
class StatusTransitions
{
    /**
     * Allowed transitions, by curation_statuses id.
     *
     *   1 Uploaded    2 Precuration   3 Disease entity assigned
     *   4 Precuration Complete        5 Curation Provisional
     *   6 Curation Approved           7 Recuration assigned
     *   8 Retired Assignment          9 Published
     */
    private const ALLOWED = [
        1 => [2],
        2 => [3],
        3 => [4],
        4 => [5],
        5 => [6],
        6 => [7, 9],
        9 => [7],
    ];

    /** A curation may be retired from any state. */
    public const TERMINAL = 8;

    /** Every curation is expected to begin here. */
    public const INITIAL = 1;

    public static function isAllowed(int $from, int $to): bool
    {
        if ($to === self::TERMINAL) {
            return true;
        }

        return in_array($to, self::ALLOWED[$from] ?? [], true);
    }

    public static function successorsOf(int $from): array
    {
        return self::ALLOWED[$from] ?? [];
    }
}
