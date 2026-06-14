<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Http\Requests\ListingIndexRequest;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListingService
{
    private const PER_PAGE = 12;

    /**
     * Create a listing owned by a regular user.
     *
     * The owner type, moderation status, and moderation timestamps are forced
     * server-side regardless of the incoming data (defense in depth).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Listing
    {
        $listing = new Listing(Arr::only($data, (new Listing)->getFillable()));

        $listing->slug = $this->generateUniqueSlug($data['title']);
        $listing->agency_id = null;
        $listing->status = ListingStatus::PendingReview;
        $listing->approved_at = null;
        $listing->rejected_at = null;
        $listing->rejection_reason = null;

        $owner->listings()->save($listing);

        return $listing;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidListingStatusTransitionException
     */
    public function update(Listing $listing, array $data): Listing
    {
        if (! $listing->canBeEditedByOwner()) {
            throw new InvalidListingStatusTransitionException('This listing cannot be edited.');
        }

        $shouldRevertToPending = $listing->shouldReturnToPendingReviewOnEdit();

        $slug = Str::slug($data['title']) === Str::slug($listing->title)
            ? $listing->slug
            : $this->generateUniqueSlug($data['title'], $listing->id);

        $listing->fill(Arr::only($data, $listing->getFillable()));
        $listing->slug = $slug;
        $listing->agency_id = null;

        if ($shouldRevertToPending) {
            $listing->status = ListingStatus::PendingReview;
            $listing->approved_at = null;
        }

        $listing->save();

        return $listing;
    }

    public function delete(Listing $listing): void
    {
        $listing->delete();
    }

    /**
     * @throws InvalidListingStatusTransitionException
     */
    public function approve(Listing $listing): Listing
    {
        if (! $listing->canBeModerated()) {
            throw new InvalidListingStatusTransitionException('This listing cannot be approved.');
        }

        $listing->status = ListingStatus::Approved;
        $listing->approved_at = now();
        $listing->rejection_reason = null;
        $listing->rejected_at = null;
        $listing->save();

        return $listing->refresh();
    }

    /**
     * @throws InvalidListingStatusTransitionException
     */
    public function reject(Listing $listing, string $reason): Listing
    {
        if (! $listing->canBeModerated()) {
            throw new InvalidListingStatusTransitionException('This listing cannot be rejected.');
        }

        $listing->status = ListingStatus::Rejected;
        $listing->rejection_reason = $reason;
        $listing->rejected_at = now();
        $listing->approved_at = null;
        $listing->save();

        $listing->delete();

        return $listing;
    }

    private function generateUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $suffix = 2;

        while (Listing::withTrashed()
            ->where('slug', $candidate)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

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
