<?php

namespace App\Policies;

use App\Curation;
use App\Policies\Traits\KnowsPrivilegedRoles;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CurationPolicy
{
    use HandlesAuthorization;
    use KnowsPrivilegedRoles;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Determine whether the user can view any uploads.
     */
    public function viewAny(User $user): bool
    {
        // return $user->hasPermissionTo('list uploads');
        return true;
    }

    public function before($user, $ability)
    {
        if ($this->hasPrivilegedRole($user)) {
            return true;
        }
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Curation $curation): bool
    {
        if ($user->id == $curation->curator_id) {
            return true;
        }

        if ($user->canEditPanelCurations($curation->expertPanel)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Curation $curation): bool
    {
        if ($this->hasPrivilegedRole($user)) {
            return true;
        }

        if ($user->isPanelCoordinator($curation->expertPanel)) {
            return true;
        }

        if ($user->can('delete curations') && $this->update($user, $curation)) {
            return true;
        }

        return false;
    }

    public function transfer(User $user, Curation $curation)
    {
        if ($this->hasPrivilegedRole($user)) {
            return true;
        }
        if ($user->isPanelCoordinator($curation->expertPanel) || $user->canEditPanelCurations($curation->expertPanel)) {
            return true;
        }

        return false;
    }
}
