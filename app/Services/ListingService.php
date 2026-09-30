<?php

namespace App\Services;

use App\Enums\Amenity;
use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Http\Requests\ListingIndexRequest;
use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use App\Services\Concerns\AppliesLikeSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListingService
{
    use AppliesLikeSearch;

    private const PER_PAGE = 12;

    private const SIMILAR_LIMIT = 3;

    private const SIMILAR_PRICE_BAND = 0.3;

    private const LATEST_LIMIT = 3;

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
        $listing->amenities = $this->normalizeAmenities($data);
        $listing->agency_id = null;
        $listing->currency = Listing::CURRENCY;
        $listing->area_unit = Listing::AREA_UNIT;
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
        $listing->amenities = $this->normalizeAmenities($data);
        $listing->agency_id = null;
        $listing->currency = Listing::CURRENCY;
        $listing->area_unit = Listing::AREA_UNIT;

        if ($shouldRevertToPending) {
            $this->revertToPendingReview($listing);
        }

        $listing->save();

        return $listing;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForAgency(Agency $agency, array $data): Listing
    {
        $listing = new Listing(Arr::only($data, (new Listing)->getFillable()));

        $listing->slug = $this->generateUniqueSlug($data['title']);
        $listing->amenities = $this->normalizeAmenities($data);
        $listing->user_id = null;
        $listing->currency = Listing::CURRENCY;
        $listing->area_unit = Listing::AREA_UNIT;
        $listing->status = ListingStatus::PendingReview;
        $listing->approved_at = null;
        $listing->rejected_at = null;
        $listing->rejection_reason = null;
        $listing->contact_name = null;
        $listing->contact_email = null;
        $listing->contact_phone = null;

        $agency->listings()->save($listing);

        return $listing;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidListingStatusTransitionException
     */
    public function updateForAgency(Listing $listing, array $data): Listing
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
        $listing->amenities = $this->normalizeAmenities($data);
        $listing->user_id = null;
        $listing->currency = Listing::CURRENCY;
        $listing->area_unit = Listing::AREA_UNIT;
        $listing->contact_name = null;
        $listing->contact_email = null;
        $listing->contact_phone = null;

        if ($shouldRevertToPending) {
            $this->revertToPendingReview($listing);
        }

        $listing->save();

        return $listing;
    }

    public function delete(Listing $listing): void
    {
        $listing->delete();
    }

    public function close(Listing $listing): Listing
    {
        if (! $listing->canBeClosedByOwner()) {
            throw new InvalidListingStatusTransitionException('This listing cannot be closed.');
        }

        $listing->status = $listing->closedStatus();
        $listing->closed_at = now();
        $listing->save();

        return $listing;
    }

    public function reopen(Listing $listing): Listing
    {
        if (! $listing->canBeReopenedByOwner()) {
            throw new InvalidListingStatusTransitionException('This listing cannot be reopened.');
        }

        $listing->status = ListingStatus::Approved;
        $listing->closed_at = null;
        $this->startLifetime($listing);
        $listing->save();

        return $listing;
    }

    /**
     * @throws InvalidListingStatusTransitionException
     */
    public function renew(Listing $listing): Listing
    {
        if (! $listing->canBeRenewedByOwner()) {
            throw new InvalidListingStatusTransitionException('This listing cannot be renewed.');
        }

        $this->startLifetime($listing);
        $listing->save();

        return $listing;
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
        $this->startLifetime($listing);
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

        return $this->markRejected($listing, $reason);
    }

    /**
     * @throws InvalidListingStatusTransitionException
     */
    public function takeDown(Listing $listing, string $reason): Listing
    {
        if (! $listing->isApproved()) {
            throw new InvalidListingStatusTransitionException('This listing cannot be taken down.');
        }

        return $this->markRejected($listing, $reason);
    }

    public function markExpiryReminderSent(Listing $listing): void
    {
        $listing->expiry_reminder_sent_at = now();
        $listing->save();
    }

    private function markRejected(Listing $listing, string $reason): Listing
    {
        $listing->status = ListingStatus::Rejected;
        $listing->rejection_reason = $reason;
        $listing->rejected_at = now();
        $listing->approved_at = null;
        $listing->save();

        $listing->delete();

        return $listing;
    }

    private function startLifetime(Listing $listing): void
    {
        $listing->expires_at = now()->addDays(Listing::LIFETIME_DAYS);
        $listing->expiry_reminder_sent_at = null;
    }

    private function revertToPendingReview(Listing $listing): void
    {
        $listing->status = ListingStatus::PendingReview;
        $listing->approved_at = null;
        $listing->expires_at = null;
        $listing->expiry_reminder_sent_at = null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, Amenity>
     */
    private function normalizeAmenities(array $data): array
    {
        $selected = $data['amenities'] ?? [];

        return array_values(array_filter(
            Amenity::cases(),
            fn (Amenity $amenity): bool => in_array($amenity->value, $selected, true),
        ));
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

        $query = $this->applyPublicFilters(
            Listing::actuallyPubliclyVisible()->with(['owner', 'images', 'agency.user']),
            $filters,
        );

        match ($filters['sort']) {
            'oldest' => $query->orderBy('approved_at'),
            'price_low_high' => $query->orderBy('price'),
            'price_high_low' => $query->orderByDesc('price'),
            default => $query->orderByDesc('approved_at'),
        };

        return $this->toCardPaginator($query->paginate(self::PER_PAGE)->withQueryString(), $request->user());
    }

    /**
     * Apply the public listing filters produced by ListingIndexRequest::filters().
     *
     * @param  Builder<Listing>  $query
     * @param  array{search: string, listingType: string, propertyType: string, city: string, minPrice: string, maxPrice: string, minBedrooms: string, minBathrooms: string, minArea: string, maxArea: string, amenities: list<string>, sort: string}  $filters
     * @return Builder<Listing>
     */
    public function applyPublicFilters(Builder $query, array $filters): Builder
    {
        if ($filters['search'] !== '') {
            $this->applyLikeSearch($query, $filters['search'], ['title', 'city', 'description']);
        }

        if ($filters['listingType'] !== '') {
            $query->where('listing_type', $filters['listingType']);
        }

        if ($filters['propertyType'] !== '') {
            $query->where('property_type', $filters['propertyType']);
        }

        if ($filters['city'] !== '') {
            $this->applyLikeSearch($query, $filters['city'], ['city']);
        }

        if ($filters['minPrice'] !== '') {
            $query->where('price', '>=', $filters['minPrice']);
        }

        if ($filters['maxPrice'] !== '') {
            $query->where('price', '<=', $filters['maxPrice']);
        }

        if ($filters['minBedrooms'] !== '') {
            $query->where('bedrooms', '>=', $filters['minBedrooms']);
        }

        if ($filters['minBathrooms'] !== '') {
            $query->where('bathrooms', '>=', $filters['minBathrooms']);
        }

        if ($filters['minArea'] !== '') {
            $query->where('area_value', '>=', $filters['minArea']);
        }

        if ($filters['maxArea'] !== '') {
            $query->where('area_value', '<=', $filters['maxArea']);
        }

        foreach ($filters['amenities'] as $amenity) {
            $query->whereJsonContains('amenities', $amenity);
        }

        return $query;
    }

    public function getPublicListingsForAgency(Agency $agency, ?User $viewer): LengthAwarePaginator
    {
        $paginator = Listing::actuallyPubliclyVisible()
            ->whereBelongsTo($agency)
            ->with(['owner', 'images', 'agency.user'])
            ->orderByDesc('approved_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return $this->toCardPaginator($paginator, $viewer);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSimilarListings(Listing $listing, User $viewer): array
    {
        $price = (float) $listing->price;

        $similar = Listing::actuallyPubliclyVisible()
            ->whereKeyNot($listing->getKey())
            ->where('listing_type', $listing->listing_type)
            ->where('property_type', $listing->property_type)
            ->whereBetween('price', [
                round($price * (1 - self::SIMILAR_PRICE_BAND), 2),
                round($price * (1 + self::SIMILAR_PRICE_BAND), 2),
            ])
            ->with(['owner', 'images', 'agency.user'])
            ->orderByRaw('city = ? desc', [$listing->city])
            ->orderByRaw('abs(price - ?)', [$listing->price])
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->limit(self::SIMILAR_LIMIT)
            ->get();

        return $this->toCardArrays($similar, $viewer)->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLatestListings(): array
    {
        return Listing::actuallyPubliclyVisible()
            ->with('images')
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->limit(self::LATEST_LIMIT)
            ->get()
            ->map(fn (Listing $listing): array => $listing->toLatestArray())
            ->values()
            ->all();
    }

    private function toCardPaginator(LengthAwarePaginator $paginator, ?User $viewer): LengthAwarePaginator
    {
        return $paginator->setCollection($this->toCardArrays($paginator->getCollection(), $viewer));
    }

    /**
     * @param  Collection<int, Listing>  $listings
     * @return Collection<int, array<string, mixed>>
     */
    private function toCardArrays(Collection $listings, ?User $viewer): Collection
    {
        $bookmarkedIds = $this->bookmarkedListingIds($viewer, $listings);

        return $listings->map(fn (Listing $listing): array => [
            ...$listing->toCardArray(),
            'isBookmarked' => $bookmarkedIds->contains($listing->id),
            'canBookmark' => $viewer === null || $viewer->can('bookmark', $listing),
        ])->values();
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
