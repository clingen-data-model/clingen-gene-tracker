<?php

namespace App\Policies;

use App\Policies\Traits\KnowsPanelCoordinators;
use App\Policies\Traits\KnowsPrivilegedRoles;
use App\Upload;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UploadPolicy
{
    use HandlesAuthorization;
    use KnowsPrivilegedRoles;
    use KnowsPanelCoordinators;

    /**
     * Determine whether the user can view any uploads.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        // return $user->hasPermissionTo('list uploads');
        return true;
    }

    /**
     * Determine whether the user can view the upload.
     *
     * @return mixed
     */
    public function view(User $user, Upload $upload)
    {
        return true;
    }

    /**
     * Determine whether the user can create uploads.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the upload.
     *
     * @return mixed
     */
    public function update(User $user, Upload $upload)
    {
        if ($this->hasPrivilegedRole($user)) {
            return true;
        }

        if ($user->id == $upload->user_id) {
            return true;
        }

        if ($this->isPanelCoordinator($user, $upload)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the upload.
     *
     * @return mixed
     */
    public function delete(User $user, Upload $upload)
    {
        if ($this->hasPrivilegedRole($user)) {
            return true;
        }

        if ($user->id == $upload->uploader_id) {
            return true;
        }

        if ($this->isPanelCoordinator($user, $upload)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the upload.
     *
     * @return mixed
     */
    public function restore(User $user, Upload $upload)
    {
        if ($this->hasPrivilegedRole($user)) {
            return true;
        }

        if ($user->id == $upload->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the upload.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Upload $upload)
    {
        if ($this->hasPrivilegedRole($user)) {
            return true;
        }

        if ($user->id == $upload->user_id) {
            return true;
        }

        return false;
    }
}
