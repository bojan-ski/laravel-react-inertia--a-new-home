<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function dismiss(User $user, Listing $listing): bool
    {
        return $user->isAdmin() && $this->hasOpenReports($listing);
    }

    public function takeDown(User $user, Listing $listing): bool
    {
        return $user->isAdmin() && $listing->isApproved() && $this->hasOpenReports($listing);
    }

    private function hasOpenReports(Listing $listing): bool
    {
        if ($listing->relationLoaded('openReports')) {
            return $listing->openReports->isNotEmpty();
        }

        return $listing->openReports()->exists();
    }
}
