<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Determine whether the user can create a listing.
     */
    public function create(User $user): bool
    {
        return $user->isRegularUser() && $user->isActive();
    }

    /**
     * Determine whether the user can edit the listing.
     */
    public function update(User $user, Listing $listing): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        if ($listing->user_id !== $user->id) {
            return false;
        }

        return $listing->canBeEditedByOwner();
    }

    /**
     * Determine whether the user can delete the listing.
     */
    public function delete(User $user, Listing $listing): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        return $listing->user_id === $user->id;
    }

    /**
     * Determine whether the user can bookmark the listing.
     */
    public function bookmark(User $user, Listing $listing): bool
    {
        return $listing->isActuallyPubliclyVisible()
            && $listing->user_id !== $user->id
            && ! $user->isAdmin()
            && $user->isActive();
    }

    /**
     * Determine whether the user can approve the listing.
     */
    public function approve(User $user, Listing $listing): bool
    {
        return $user->isAdmin() && $listing->canBeModerated();
    }

    /**
     * Determine whether the user can reject the listing.
     */
    public function reject(User $user, Listing $listing): bool
    {
        return $user->isAdmin() && $listing->canBeModerated();
    }
}
