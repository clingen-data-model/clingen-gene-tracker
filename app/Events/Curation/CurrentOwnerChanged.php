<?php

namespace App\Events\Curation;

use App\Curation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired only when the projector moves a curation's current owner, never on mere
 * ingestion of an ownership event. Replaying an already-applied transfer, or
 * applying one dated before the current owner's, changes nothing and so fires
 * nothing -- which is what keeps replay from re-sending notifications.
 */
class CurrentOwnerChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Curation $curation,
        public ?int $previousExpertPanelId,
        public int $expertPanelId
    ) {
    }
}
