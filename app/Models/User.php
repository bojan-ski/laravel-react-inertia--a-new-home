<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::RegularUser->value,
        'status' => UserStatus::Active->value,
    ];

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function agency(): HasOne
    {
        return $this->hasOne(Agency::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedListings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'bookmarks')->withTimestamps();
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function hasBookmarked(Listing $listing): bool
    {
        return $this->bookmarks()->where('listing_id', $listing->id)->exists();
    }

    public function isRegularUser(): bool
    {
        return $this->role === UserRole::RegularUser;
    }

    public function isAgency(): bool
    {
        return $this->role === UserRole::Agency;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(self $actor): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'suspendedAt' => $this->suspended_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'agency' => null,
            'can' => [
                'canSuspend' => $actor->can('suspend', $this),
                'canReactivate' => $actor->can('reactivate', $this),
                'canDelete' => $actor->can('delete', $this),
            ],
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'suspended_at' => 'datetime',
        ];
    }
}
