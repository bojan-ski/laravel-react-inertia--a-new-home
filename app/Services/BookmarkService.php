<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

    public function add(User $user, Listing $listing): void
    {
        if ($user->bookmarks()->where('listing_id', $listing->id)->exists()) {
            return;
        }

        $bookmark = new Bookmark;
        $bookmark->listing_id = $listing->id;
        $user->bookmarks()->save($bookmark);
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

    /**
     * @return Collection<int, Listing>
     */
    public function getUnavailableBookmarkedListings(User $user): Collection
    {
        return $user->bookmarkedListings()
            ->where(fn (Builder $query) => $query->closed()->orWhere(fn (Builder $query) => $query->expired()))
            ->ownerNotSuspended()
            ->with(['owner', 'images', 'agency.user'])
            ->orderByPivot('created_at', 'desc')
            ->get();
    }
}
