<?php

namespace App\Services;

use App\Http\Requests\ListingIndexRequest;
use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }
}
