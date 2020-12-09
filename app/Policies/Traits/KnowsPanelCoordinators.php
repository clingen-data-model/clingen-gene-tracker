<?php

namespace App\Policies\Traits;

trait KnowsPanelCoordinators
{
    private function isPanelCoordinator($user, $upload)
    {
        if ($user->isPanelCoordinator($upload->curation->expertPanel)) {
            return true;
        }

        return false;
    }
}
