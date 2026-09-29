<?php

namespace App\Models;

use App\Enums\Amenity;
use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title',
    'listing_type',
    'property_type',
    'price',
    'description',
    'country',
    'city',
    'address_line',
    'postal_code',
    'bedrooms',
    'bathrooms',
    'area_value',
    'year_built',
    'amenities',
    'contact_name',
    'contact_email',
    'contact_phone',
])]
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory, SoftDeletes;

    public const CURRENCY = 'EUR';

    public const AREA_UNIT = 'm²';

    public const LIFETIME_DAYS = 90;

    public const EXPIRY_REMINDER_DAYS = 7;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ListingStatus::PendingReview->value,
        'currency' => self::CURRENCY,
        'area_unit' => self::AREA_UNIT,
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ListingReport::class);
    }

    public function openReports(): HasMany
    {
        return $this->reports()->whereNull('resolved_at');
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where('status', ListingStatus::Approved->value)
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', now())
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    #[Scope]
    protected function expired(Builder $query): void
    {
        $query->where('status', ListingStatus::Approved->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    #[Scope]
    protected function dueForExpiryReminder(Builder $query): void
    {
        $query->where('status', ListingStatus::Approved->value)
            ->whereNull('expiry_reminder_sent_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(self::EXPIRY_REMINDER_DAYS));
    }

    #[Scope]
    protected function actuallyPubliclyVisible(Builder $query): void
    {
        $query->publiclyVisible()->ownerNotSuspended();
    }

    #[Scope]
    protected function ownerNotSuspended(Builder $query): void
    {
        $query
            ->whereDoesntHave('owner', function (Builder $query) {
                $query->where('status', UserStatus::Suspended->value);
            })
            ->whereDoesntHave('agency.user', function (Builder $query) {
                $query->where('status', UserStatus::Suspended->value);
            });
    }

    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->whereIn('status', [ListingStatus::Sold->value, ListingStatus::Rented->value]);
    }

    #[Scope]
    protected function byStatus(Builder $query, ListingStatus $status): void
    {
        $query->where('status', $status->value);
    }

    #[Scope]
    protected function byOwner(Builder $query, User $user): void
    {
        $query->whereBelongsTo($user, 'owner');
    }

    public function isPendingReview(): bool
    {
        return $this->status === ListingStatus::PendingReview;
    }

    public function isApproved(): bool
    {
        return $this->status === ListingStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ListingStatus::Rejected;
    }

    public function isHidden(): bool
    {
        return $this->status === ListingStatus::Hidden;
    }

    public function isSold(): bool
    {
        return $this->status === ListingStatus::Sold;
    }

    public function isRented(): bool
    {
        return $this->status === ListingStatus::Rented;
    }

    public function isClosed(): bool
    {
        return $this->isSold() || $this->isRented();
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isApproved()
            && $this->approved_at !== null
            && $this->approved_at->lessThanOrEqualTo(now())
            && ! $this->isExpired()
            && ! $this->trashed();
    }

    public function isExpired(): bool
    {
        return $this->isApproved()
            && $this->expires_at !== null
            && $this->expires_at->lessThanOrEqualTo(now());
    }

    public function canBeRenewedByOwner(): bool
    {
        return $this->isApproved()
            && $this->expires_at !== null
            && $this->expires_at->lessThanOrEqualTo(now()->addDays(self::EXPIRY_REMINDER_DAYS));
    }

    public function shouldBeHiddenDueToOwnerSuspension(): bool
    {
        if ($this->user_id !== null) {
            return (bool) $this->owner?->isSuspended();
        }

        if ($this->agency_id !== null) {
            return (bool) $this->agency?->user?->isSuspended();
        }

        return false;
    }

    public function isActuallyPubliclyVisible(): bool
    {
        return $this->isPubliclyVisible() && ! $this->shouldBeHiddenDueToOwnerSuspension();
    }

    public function canBeEditedByOwner(): bool
    {
        return in_array($this->status, [
            ListingStatus::PendingReview,
            ListingStatus::Approved,
            ListingStatus::Hidden,
        ], true);
    }

    public function canBeModerated(): bool
    {
        return $this->status === ListingStatus::PendingReview;
    }

    public function shouldReturnToPendingReviewOnEdit(): bool
    {
        return $this->status === ListingStatus::Approved;
    }

    public function canBeClosedByOwner(): bool
    {
        return $this->status === ListingStatus::Approved;
    }

    public function canBeReopenedByOwner(): bool
    {
        return $this->isClosed();
    }

    public function closedStatus(): ListingStatus
    {
        return $this->listing_type === 'rent' ? ListingStatus::Rented : ListingStatus::Sold;
    }

    public function ownerName(): ?string
    {
        if ($this->relationLoaded('owner') && $this->owner !== null) {
            return $this->owner->name;
        }

        if ($this->relationLoaded('agency') && $this->agency !== null) {
            return $this->agency->name;
        }

        return null;
    }

    public static function formatPrice(float|int|string $amount): string
    {
        return '€'.number_format((float) $amount);
    }

    /**
     * @return array{title: string, url: string, price: string, city: string}
     */
    public function toDigestArray(): array
    {
        return [
            'title' => $this->title,
            'url' => route('listings.show', $this->slug),
            'price' => self::formatPrice($this->price),
            'city' => $this->city,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toCardArray(): array
    {
        $image = $this->relationLoaded('images') ? $this->images->first() : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'listingType' => $this->listing_type,
            'propertyType' => $this->property_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'city' => $this->city,
            'country' => $this->country,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'areaValue' => $this->area_value,
            'areaUnit' => $this->area_unit,
            'approvedAt' => $this->approved_at?->toIso8601String(),
            'imageUrl' => $image?->small_path
                ? Storage::disk($image->disk)->url($image->small_path)
                : null,
            'ownerName' => $this->ownerName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toOwnerDashboardArray(): array
    {
        return [
            ...$this->toCardArray(),
            'status' => $this->status->value,
            'createdAt' => $this->created_at?->toIso8601String(),
            'rejectionReason' => $this->rejection_reason,
            'closedAt' => $this->closed_at?->toIso8601String(),
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'isExpired' => $this->isExpired(),
            'canRenew' => $this->canBeRenewedByOwner(),
        ];
    }

    public function toBookmarkCardArray(): array
    {
        return [
            ...$this->toCardArray(),
            'status' => $this->status->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function amenityValues(): array
    {
        return $this->amenities
            ?->map(fn (Amenity $amenity): string => $amenity->value)
            ->values()
            ->all() ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function imagesForShow(): array
    {
        return $this->relationLoaded('images')
            ? $this->images->map(fn (ListingImage $image): array => [
                'id' => $image->id,
                'url' => Storage::disk($image->disk)->url($image->large_path),
                'altText' => $image->alt_text ?: $this->title,
                'sortOrder' => $image->sort_order,
            ])->all()
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toShowArray(): array
    {
        $images = $this->imagesForShow();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'listingType' => $this->listing_type,
            'propertyType' => $this->property_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'description' => $this->description,
            'country' => $this->country,
            'city' => $this->city,
            'addressLine' => $this->address_line,
            'postalCode' => $this->postal_code,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'areaValue' => $this->area_value,
            'areaUnit' => $this->area_unit,
            'yearBuilt' => $this->year_built,
            'contactName' => $this->contact_name,
            'contactEmail' => $this->contact_email,
            'contactPhone' => $this->contact_phone,
            'amenities' => $this->amenityValues(),
            'approvedAt' => $this->approved_at?->format('F j, Y'),
            'images' => $images,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        $images = $this->imagesForShow();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'listingType' => $this->listing_type,
            'propertyType' => $this->property_type,
            'status' => $this->status->value,
            'price' => $this->price,
            'currency' => $this->currency,
            'description' => $this->description,
            'country' => $this->country,
            'city' => $this->city,
            'addressLine' => $this->address_line,
            'postalCode' => $this->postal_code,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'areaValue' => $this->area_value,
            'areaUnit' => $this->area_unit,
            'yearBuilt' => $this->year_built,
            'contactName' => $this->contact_name,
            'contactEmail' => $this->contact_email,
            'contactPhone' => $this->contact_phone,
            'amenities' => $this->amenityValues(),
            'images' => $images,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toModerationArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'listingType' => $this->listing_type,
            'propertyType' => $this->property_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'city' => $this->city,
            'country' => $this->country,
            'ownerName' => $this->owner?->name ?? $this->agency?->name,
            'ownerEmail' => $this->owner?->email ?? $this->agency?->email,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toReportModerationArray(User $actor): array
    {
        $image = $this->relationLoaded('images') ? $this->images->first() : null;
        $reports = $this->relationLoaded('openReports') ? $this->openReports : collect();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'imageUrl' => $image?->small_path
                ? Storage::disk($image->disk)->url($image->small_path)
                : null,
            'ownerName' => $this->owner?->name ?? $this->agency?->name,
            'ownerEmail' => $this->owner?->email ?? $this->agency?->email,
            'openReportsCount' => $this->open_reports_count ?? $reports->count(),
            'reports' => $reports
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (ListingReport $report): array => $report->toModerationArray())
                ->all(),
            'canTakeDown' => $actor->can('takeDown', [ListingReport::class, $this]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toReviewArray(): array
    {
        return [
            ...$this->toShowArray(),
            'status' => $this->status->value,
            'rejectionReason' => $this->rejection_reason,
            'ownerType' => $this->agency_id !== null ? 'agency' : 'user',
            'ownerName' => $this->owner?->name ?? $this->agency?->name,
            'ownerEmail' => $this->owner?->email ?? $this->agency?->email,
            'createdAt' => $this->created_at?->toIso8601String(),
            'expiresAt' => $this->isApproved() ? $this->expires_at?->toIso8601String() : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'amenities' => AsEnumCollection::of(Amenity::class),
            'price' => 'decimal:2',
            'area_value' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'closed_at' => 'datetime',
            'expires_at' => 'datetime',
            'expiry_reminder_sent_at' => 'datetime',
        ];
    }
}
