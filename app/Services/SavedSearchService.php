<?php

namespace App\Services;

use App\Exceptions\InvalidSavedSearchException;
use App\Http\Requests\ListingIndexRequest;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SavedSearchService
{
    public function __construct(
        private ListingService $listings,
        private MailNotificationService $mail,
    ) {}

    /**
     * @param  array<string, mixed>  $filters  the normalized ListingIndexRequest::filters() output
     *
     * @throws InvalidSavedSearchException
     */
    public function save(User $user, array $filters, ?string $name, bool $alertsEnabled = true): SavedSearch
    {
        $stored = Arr::except($filters, 'sort');

        if ($this->isEmptyFilterSet($stored)) {
            throw new InvalidSavedSearchException(__('app.saved_searches.errors.empty'));
        }

        return DB::transaction(function () use ($user, $stored, $name, $alertsEnabled): SavedSearch {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $existing = $user->savedSearches()->get();

            if ($existing->contains(fn (SavedSearch $search): bool => $search->filterState() === $stored)) {
                throw new InvalidSavedSearchException(__('app.saved_searches.errors.duplicate'));
            }

            if ($existing->count() >= SavedSearch::MAX_PER_USER) {
                throw new InvalidSavedSearchException(
                    __('app.saved_searches.errors.limit', ['max' => SavedSearch::MAX_PER_USER])
                );
            }

            $name = trim((string) $name);

            $search = new SavedSearch([
                'name' => $name !== '' ? $name : $this->suggestName($stored),
                'alerts_enabled' => $alertsEnabled,
            ]);

            $search->user_id = $user->id;
            $search->filters = $stored;
            $search->last_alerted_at = now();
            $search->save();

            return $search;
        });
    }

    /**
     * @param  array{name?: string, alerts_enabled?: bool|int|string}  $data
     */
    public function update(SavedSearch $search, array $data): SavedSearch
    {
        if (array_key_exists('name', $data)) {
            $search->name = trim($data['name']);
        }

        if (array_key_exists('alerts_enabled', $data)) {
            $alertsEnabled = (bool) $data['alerts_enabled'];
            $enabling = $alertsEnabled && ! $search->alerts_enabled;

            $search->alerts_enabled = $alertsEnabled;

            if ($enabling) {
                $search->last_alerted_at = now();
            }
        }

        $search->save();

        return $search;
    }

    public function delete(SavedSearch $search): void
    {
        $search->delete();
    }

    public function disableAllAlerts(User $user): int
    {
        return $user->savedSearches()
            ->where('alerts_enabled', true)
            ->update(['alerts_enabled' => false]);
    }

    /**
     * Human-readable parts of a filter set, e.g. ["Rent", "Apartment", "Belgrade", "2+ beds"].
     *
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    public function summarize(array $filters): array
    {
        $filters = array_replace(ListingIndexRequest::emptyFilters(), $filters);
        $parts = [];

        if ($filters['search'] !== '') {
            $parts[] = __('app.saved_searches.summary.search', ['term' => $filters['search']]);
        }

        if ($filters['listingType'] !== '') {
            $parts[] = __($filters['listingType'] === 'rent' ? 'app.listings.filters.rent' : 'app.listings.filters.buy');
        }

        if ($filters['propertyType'] !== '') {
            $parts[] = __("app.listings.filters.property_types.{$filters['propertyType']}");
        }

        if ($filters['city'] !== '') {
            $parts[] = $filters['city'];
        }

        $price = $this->rangePart(
            $filters['minPrice'],
            $filters['maxPrice'],
            fn (string $value): string => Listing::formatPrice($value),
            'price',
        );

        if ($price !== null) {
            $parts[] = $price;
        }

        if ($filters['minBedrooms'] !== '') {
            $parts[] = __('app.listings.filters.beds_option', ['count' => $filters['minBedrooms']]);
        }

        if ($filters['minBathrooms'] !== '') {
            $parts[] = __('app.listings.filters.baths_option', ['count' => $filters['minBathrooms']]);
        }

        $area = $this->rangePart(
            $filters['minArea'],
            $filters['maxArea'],
            fn (string $value): string => $this->formatNumber($value),
            'area',
        );

        if ($area !== null) {
            $parts[] = $area;
        }

        foreach ($filters['amenities'] as $amenity) {
            $parts[] = __("app.listings.amenities.{$amenity}");
        }

        return $parts;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function suggestName(array $filters): string
    {
        return Str::limit(implode(' · ', $this->summarize($filters)), 99, '…');
    }

    /**
     * Re-validate a search's stored filters, drop keys that are no longer valid,
     * and merge the rest over the full filter shape.
     *
     * @return array{search: string, listingType: string, propertyType: string, city: string, minPrice: string, maxPrice: string, minBedrooms: string, minBathrooms: string, minArea: string, maxArea: string, amenities: list<string>, sort: string}
     */
    public function resolveFilters(SavedSearch $search): array
    {
        $stored = is_array($search->filters) ? $search->filters : [];

        $errors = Validator::make($stored, ListingIndexRequest::filterRules())->errors();

        foreach (array_keys($errors->messages()) as $key) {
            if (! str_starts_with($key, 'amenities.')) {
                unset($stored[$key]);
            }
        }

        return ListingIndexRequest::normalizeFilters($stored);
    }

    /**
     * Email the user one digest covering every alert-enabled search that has
     * new matches, then move all their alert marks to the run start.
     */
    public function sendDigest(User $user, CarbonInterface $runStartedAt): bool
    {
        $searches = $user->savedSearches->where('alerts_enabled', true);
        $sections = [];

        foreach ($searches as $search) {
            $filters = $this->resolveFilters($search);

            if ($this->isEmptyFilterSet($filters)) {
                continue;
            }

            $query = $this->newMatchesQuery($user, $search, $filters, $runStartedAt);
            $total = (clone $query)->count();

            if ($total === 0) {
                continue;
            }

            $sections[] = [
                'name' => $search->name,
                'url' => $search->listingsUrl(),
                'listings' => $query
                    ->orderByDesc('approved_at')
                    ->limit(SavedSearch::DIGEST_LISTING_LIMIT)
                    ->get()
                    ->map(fn (Listing $listing): array => $listing->toDigestArray())
                    ->all(),
                'total' => $total,
            ];
        }

        if ($sections !== []) {
            $this->mail->sendSavedSearchDigest($user, $sections);
        }

        SavedSearch::query()
            ->whereKey($searches->modelKeys())
            ->update(['last_alerted_at' => $runStartedAt]);

        return $sections !== [];
    }

    /**
     * A search whose filters are all unset (or were all dropped as invalid)
     * would match every listing, so it is never saved or alerted on.
     *
     * @param  array<string, mixed>  $filters
     */
    private function isEmptyFilterSet(array $filters): bool
    {
        return Arr::except($filters, 'sort') === Arr::except(ListingIndexRequest::emptyFilters(), 'sort');
    }

    /**
     * @param  array{search: string, listingType: string, propertyType: string, city: string, minPrice: string, maxPrice: string, minBedrooms: string, minBathrooms: string, minArea: string, maxArea: string, amenities: list<string>, sort: string}  $filters
     * @return Builder<Listing>
     */
    private function newMatchesQuery(User $user, SavedSearch $search, array $filters, CarbonInterface $runStartedAt): Builder
    {
        $query = $this->listings->applyPublicFilters(Listing::actuallyPubliclyVisible(), $filters);

        $query
            ->when($search->last_alerted_at, fn (Builder $query, CarbonInterface $since) => $query->where('approved_at', '>', $since))
            ->where('approved_at', '<=', $runStartedAt)
            ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $user->id));

        if ($user->agency !== null) {
            $query->where(fn (Builder $query) => $query->whereNull('agency_id')->orWhere('agency_id', '!=', $user->agency->id));
        }

        return $query;
    }

    private function rangePart(string $min, string $max, callable $format, string $key): ?string
    {
        if ($min !== '' && $max !== '') {
            return __("app.saved_searches.summary.{$key}_range", ['min' => $format($min), 'max' => $format($max)]);
        }

        if ($min !== '') {
            return __("app.saved_searches.summary.{$key}_from", ['min' => $format($min)]);
        }

        if ($max !== '') {
            return __("app.saved_searches.summary.{$key}_up_to", ['max' => $format($max)]);
        }

        return null;
    }

    private function formatNumber(string $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    }
}
