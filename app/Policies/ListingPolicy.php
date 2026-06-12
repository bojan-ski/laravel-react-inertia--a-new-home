<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
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
}
