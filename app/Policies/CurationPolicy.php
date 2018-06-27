<?php

namespace App\Policies;

use App\Curation;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CurationPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function before($user, $ability)
    {
        if ($user->hasRole('programmer|admin')) {
            return true;
        }
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Curation $curation)
    {
        if ($user->id == $curation->curator_id) {
            return true;
        }

        if ($user->canEditPanelCurations($curation->expertPanel)) {
            return true;
        }

        return false;
    }
}
