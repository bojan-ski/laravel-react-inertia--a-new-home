<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
    'currency',
    'description',
    'country',
    'city',
    'address_line',
    'postal_code',
    'bedrooms',
    'bathrooms',
    'area_value',
    'area_unit',
    'year_built',
    'contact_name',
    'contact_email',
    'contact_phone',
])]
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ListingStatus::PendingReview->value,
        'currency' => 'EUR',
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

    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where('status', ListingStatus::Approved->value)
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', now());
    }

    #[Scope]
    protected function actuallyPubliclyVisible(Builder $query): void
    {
        $query->publiclyVisible()
            ->where(function (Builder $query) {
                $query->whereNull($query->qualifyColumn('user_id'))
                    ->orWhereHas('owner', function (Builder $query) {
                        $query->where('status', '!=', UserStatus::Suspended->value);
                    });
            });
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

    public function isPubliclyVisible(): bool
    {
        return $this->isApproved()
            && $this->approved_at !== null
            && $this->approved_at->lessThanOrEqualTo(now())
            && ! $this->trashed();
    }

    public function shouldBeHiddenDueToOwnerSuspension(): bool
    {
        if ($this->user_id !== null) {
            return (bool) $this->owner?->isSuspended();
        }

        if ($this->agency_id !== null) {
            return (bool) $this->agency?->owner?->isSuspended();
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
            'imageUrl' => $image?->small_webp_path
                ? Storage::disk('public')->url($image->small_webp_path)
                : null,
            'ownerName' => $this->ownerName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toShowArray(): array
    {
        $images = $this->relationLoaded('images')
            ? $this->images->map(fn (ListingImage $image): array => [
                'id' => $image->id,
                'url' => Storage::disk('public')->url($image->large_webp_path),
                'altText' => $this->title,
                'sortOrder' => $image->sort_order,
            ])->all()
            : [];

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
            'approvedAt' => $this->approved_at?->format('F j, Y'),
            'images' => $images,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        $images = $this->relationLoaded('images')
            ? $this->images->map(fn (ListingImage $image): array => [
                'id' => $image->id,
                'url' => Storage::disk('public')->url($image->large_webp_path),
                'altText' => $this->title,
                'sortOrder' => $image->sort_order,
            ])->all()
            : [];

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
            'images' => $images,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'price' => 'decimal:2',
            'area_value' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
