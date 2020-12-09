<?php

namespace App\Policies\Traits;

/**
 * Privides helper functions to make policies more declarative and easier to write.
 */
trait KnowsPrivilegedRoles
{
    private function hasPrivilegedRole($user, $roles = null)
    {
        $privilegedRoles = $roles ?? ['programmer', 'admin'];

        return $user->hasAnyRole($privilegedRoles);
    }
}
