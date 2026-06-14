<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
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
