<?php

namespace App\Curations;

use App\Classification;
use App\CurationStatus;
use App\ExpertPanel;

/**
 * The curation fields whose history is tracked as a dated, source-keyed event log.
 *
 * Each case owns the per-field policy that used to be scattered across AddStatus,
 * AddClassification and SetOwner, where the three had drifted apart.
 */
enum CurationField: string
{
    case Status = 'status';
    case Classification = 'classification';
    case ExpertPanel = 'expert_panel';

    /** Denormalized "current value" column on `curations`, if the field has one. */
    public function currentValueColumn(): ?string
    {
        return match ($this) {
            self::Status => 'curation_status_id',
            self::ExpertPanel => 'expert_panel_id',
            // Current classification is derived, not stored.
            self::Classification => null,
        };
    }

    public function historyTable(): string
    {
        return match ($this) {
            self::Status => 'curation_curation_status',
            self::Classification => 'classification_curation',
            self::ExpertPanel => 'curation_expert_panel',
        };
    }

    public function valueColumn(): string
    {
        return match ($this) {
            self::Status => 'curation_status_id',
            self::Classification => 'classification_id',
            self::ExpertPanel => 'expert_panel_id',
        };
    }

    public function dateColumn(): string
    {
        return match ($this) {
            self::Status => 'status_date',
            self::Classification => 'classification_date',
            self::ExpertPanel => 'start_date',
        };
    }

    /**
     * Ownership is rendered as intervals, so its rows carry a derived `end_date`.
     */
    public function isInterval(): bool
    {
        return $this === self::ExpertPanel;
    }

    /**
     * When true, an event asserting the value the timeline already holds at that
     * point is not recorded. History reads as a list of transitions.
     */
    public function collapsesConsecutiveDuplicates(): bool
    {
        return true;
    }

    /** @return class-string */
    public function valueModel(): string
    {
        return match ($this) {
            self::Status => CurationStatus::class,
            self::Classification => Classification::class,
            self::ExpertPanel => ExpertPanel::class,
        };
    }

    public function relation(): string
    {
        return match ($this) {
            self::Status => 'curationStatuses',
            self::Classification => 'classifications',
            self::ExpertPanel => 'expertPanels',
        };
    }
}
