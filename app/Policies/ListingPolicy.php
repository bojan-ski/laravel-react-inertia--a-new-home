<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Listing $listing): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        return $listing->isPubliclyVisible();
    }

    public function create(User $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isRegularUser()) {
            return true;
        }

        if ($user->isAgency()) {
            return $user->agency !== null && $user->agency->isApproved();
        }

        return false;
    }

    public function update(User $user, Listing $listing): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        if ($listing->agency_id !== null) {
            return $this->ownsApprovedAgency($user, $listing)
                && $listing->canBeEditedByOwner();
        }

        if ($listing->user_id !== $user->id) {
            return false;
        }

        return $listing->canBeEditedByOwner();
    }

    public function delete(User $user, Listing $listing): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        if ($listing->agency_id !== null) {
            return $this->ownsApprovedAgency($user, $listing);
        }

        return $listing->user_id === $user->id;
    }

    private function ownsApprovedAgency(User $user, Listing $listing): bool
    {
        $agency = $user->agency;

        return $agency !== null
            && $listing->agency_id === $agency->id
            && $agency->isApproved();
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
