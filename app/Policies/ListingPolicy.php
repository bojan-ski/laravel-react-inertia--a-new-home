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

    public function close(User $user, Listing $listing): bool
    {
        return $this->managesListing($user, $listing) && $listing->canBeClosedByOwner();
    }

    public function reopen(User $user, Listing $listing): bool
    {
        return $this->managesListing($user, $listing) && $listing->canBeReopenedByOwner();
    }

    private function managesListing(User $user, Listing $listing): bool
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

    private function ownsThroughAgency(User $user, Listing $listing): bool
    {
        return $listing->agency_id !== null
            && $listing->agency_id === $user->agency?->id;
    }

    /**
     * Determine whether the user can bookmark the listing.
     */
    public function bookmark(User $user, Listing $listing): bool
    {
        return $listing->isActuallyPubliclyVisible()
            && $listing->user_id !== $user->id
            && ! $this->ownsThroughAgency($user, $listing)
            && ! $user->isAdmin()
            && $user->isActive();
    }

    public function removeBookmark(User $user, Listing $listing): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($listing->relationLoaded('pivot') && (int) $listing->pivot->user_id === $user->id) {
            return true;
        }

        return $user->bookmarks()->where('listing_id', $listing->id)->exists();
    }

    /**
     * Determine whether the user can open the admin review page of the listing.
     */
    public function review(User $user, Listing $listing): bool
    {
        return $user->isAdmin();
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
