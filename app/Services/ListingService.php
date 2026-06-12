<?php

namespace App\Services;

use App\Http\Requests\ListingIndexRequest;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListingService
{
    private const PER_PAGE = 12;

    public function getPublicListings(ListingIndexRequest $request): LengthAwarePaginator
    {
        $filters = $request->filters();

        $query = Listing::actuallyPubliclyVisible()->with(['owner', 'images']);

        if ($filters['search'] !== '') {
            $query->where(function (Builder $query) use ($filters) {
                $query->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('city', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if ($filters['listingType'] !== '') {
            $query->where('listing_type', $filters['listingType']);
        }

        if ($filters['propertyType'] !== '') {
            $query->where('property_type', $filters['propertyType']);
        }

        if ($filters['city'] !== '') {
            $query->where('city', 'like', "%{$filters['city']}%");
        }

        if ($filters['minPrice'] !== '') {
            $query->where('price', '>=', $filters['minPrice']);
        }

        if ($filters['maxPrice'] !== '') {
            $query->where('price', '<=', $filters['maxPrice']);
        }

        match ($filters['sort']) {
            'oldest' => $query->orderBy('approved_at'),
            'price_low_high' => $query->orderBy('price'),
            'price_high_low' => $query->orderByDesc('price'),
            default => $query->orderByDesc('approved_at'),
        };

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        $bookmarkedIds = $this->bookmarkedListingIds($request->user(), $paginator->getCollection());

        return $paginator->through(fn (Listing $listing): array => [
            ...$listing->toCardArray(),
            'isBookmarked' => $bookmarkedIds->contains($listing->id),
        ]);
    }

    /**
     * @param  Collection<int, Listing>  $listings
     * @return Collection<int, int>
     */
    private function bookmarkedListingIds(?User $user, Collection $listings): Collection
    {
        if ($user === null) {
            return collect();
        }

        return $user->bookmarks()
            ->whereIn('listing_id', $listings->pluck('id'))
            ->pluck('listing_id');
    }
}
