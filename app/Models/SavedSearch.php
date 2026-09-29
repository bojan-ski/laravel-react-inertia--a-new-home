<?php

namespace App\Models;

use App\Http\Requests\ListingIndexRequest;
use Database\Factories\SavedSearchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

#[Fillable(['name', 'alerts_enabled'])]
class SavedSearch extends Model
{
    /** @use HasFactory<SavedSearchFactory> */
    use HasFactory;

    public const MAX_PER_USER = 10;

    public const DIGEST_LISTING_LIMIT = 10;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'alerts_enabled' => 'boolean',
            'last_alerted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The stored filters over the full filter shape (without sort), in shape order.
     *
     * @return array<string, string|list<string>>
     */
    public function filterState(): array
    {
        $shape = Arr::except(ListingIndexRequest::emptyFilters(), 'sort');

        return array_replace($shape, array_intersect_key($this->filters ?? [], $shape));
    }

    /**
     * Only the filters that are set, as used in the listings query string.
     *
     * @return array<string, string|list<string>>
     */
    public function activeFilters(): array
    {
        return array_filter(
            $this->filterState(),
            fn (string|array $value): bool => $value !== '' && $value !== [],
        );
    }

    public function listingsUrl(): string
    {
        return route('listings.index', $this->activeFilters());
    }

    /**
     * @return array<string, mixed>
     */
    public function toManageArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'filters' => $this->activeFilters(),
            'alertsEnabled' => $this->alerts_enabled,
            'createdAt' => $this->created_at?->toIso8601String(),
            'url' => $this->listingsUrl(),
        ];
    }
}
