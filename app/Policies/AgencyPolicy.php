<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Agency $agency): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        return $user->isAgency() && $agency->user_id === $user->id;
    }

    public function update(User $user, Agency $agency): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        return $user->isAgency() && $agency->user_id === $user->id;
    }

    /**
     * Determine whether anyone (including guests) can view the agency on the
     * public directory. Nullable user because guests reach this surface.
     */
    public function viewPublic(?User $user, Agency $agency): bool
    {
        return $agency->isPubliclyVisible();
    }

    /**
     * Determine whether the user can approve the agency.
     */
    public function approve(User $user, Agency $agency): bool
    {
        return $user->isAdmin() && $agency->canBeModerated();
    }

    /**
     * Determine whether the user can reject the agency.
     */
    public function reject(User $user, Agency $agency): bool
    {
        return $user->isAdmin() && $agency->canBeModerated();
    }
}
