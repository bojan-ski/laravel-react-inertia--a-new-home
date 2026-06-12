<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Collection;

class BookmarkService
{
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
            ->with(['owner', 'images'])
            ->orderByPivot('created_at', 'desc')
            ->get();
    }
}
