<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Collection;

class BookmarkService
{
    public function hasBookmarked(User $user, Listing $listing): bool
    {
        return $user->bookmarks()->where('listing_id', $listing->id)->exists();
    }

    public function remove(User $user, Listing $listing): void
    {
        $user->bookmarks()->where('listing_id', $listing->id)->delete();
    }

    public function toggle(User $user, Listing $listing): bool
    {
        $bookmark = $user->bookmarks()->where('listing_id', $listing->id)->first();

        if ($bookmark !== null) {
            $bookmark->delete();

            return false;
        }

        $user->bookmarks()->create(['listing_id' => $listing->id]);

        return true;
    }

    /**
     * @return Collection<int, Listing>
     */
    public function getBookmarkedListings(User $user): Collection
    {
        return $user->bookmarkedListings()
            ->actuallyPubliclyVisible()
            ->with(['owner', 'images', 'agency.user'])
            ->orderByPivot('created_at', 'desc')
            ->get();
    }

    public function getClosedBookmarkedListings(User $user): Collection
    {
        return $user->bookmarkedListings()
            ->closed()
            ->ownerNotSuspended()
            ->with(['owner', 'images', 'agency.user'])
            ->orderByPivot('created_at', 'desc')
            ->get();
    }
}
