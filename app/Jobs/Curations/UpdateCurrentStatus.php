<?php

namespace App\Jobs\Curations;

use App\Actions\Curations\ProjectCurationField;
use App\Curation;
use App\Curations\CurationField;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Kept as the name the controllers already call; the work is the projector's.
 */
class UpdateCurrentStatus
{
    use Dispatchable;

    private Curation $curation;

    public function __construct(Curation $curation)
    {
        $this->curation = $curation;
    }

    public function handle()
    {
        ProjectCurationField::run($this->curation, CurationField::Status);
    }
}
