<?php

namespace App\Models;

use App\Enums\AgencyStatus;
use App\Enums\UserStatus;
use App\Enums\UsState;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'legal_name',
    'ein',
    'license_number',
    'license_state',
    'description',
    'phone',
    'email',
    'website',
    'address',
])]
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => AgencyStatus::PendingVerification->value,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public const PROFILE_FIELDS = [
        'name',
        'legal_name',
        'ein',
        'license_number',
        'license_state',
        'email',
        'phone',
        'website',
        'address',
        'description',
    ];

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function pendingChange(): HasOne
    {
        return $this->hasOne(AgencyProfileChange::class);
    }

    public function profileValue(string $field): ?string
    {
        $value = $this->getAttribute($field);

        if ($value instanceof UsState) {
            return $value->value;
        }

        return $value === null ? null : (string) $value;
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where('status', AgencyStatus::Approved->value)
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', now());
    }

    #[Scope]
    protected function actuallyPubliclyVisible(Builder $query): void
    {
        $query->publiclyVisible()
            ->whereDoesntHave('user', function (Builder $query) {
                $query->where('status', UserStatus::Suspended->value);
            });
    }

    #[Scope]
    protected function byStatus(Builder $query, AgencyStatus $status): void
    {
        $query->where('status', $status->value);
    }

    public function isPendingVerification(): bool
    {
        return $this->status === AgencyStatus::PendingVerification;
    }

    public function isApproved(): bool
    {
        return $this->status === AgencyStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === AgencyStatus::Rejected;
    }

    public function isHidden(): bool
    {
        return $this->status === AgencyStatus::Hidden;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isApproved()
            && $this->approved_at !== null
            && $this->approved_at->lessThanOrEqualTo(now());
    }

    public function shouldBeHiddenDueToSuspension(): bool
    {
        return (bool) $this->user?->isSuspended();
    }

    public function isActuallyPubliclyVisible(): bool
    {
        return $this->isPubliclyVisible() && ! $this->shouldBeHiddenDueToSuspension();
    }

    public function canBeModerated(): bool
    {
        return $this->status === AgencyStatus::PendingVerification;
    }

    /**
     * @return array<string, mixed>
     */
    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'description' => $this->description,
            'licenseNumber' => $this->license_number,
            'licenseState' => $this->license_state?->value,
            'approvedAt' => $this->approved_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toModerationArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'description' => $this->description,
            ...$this->businessDetails(),
            'ownerName' => $this->user?->name,
            'ownerEmail' => $this->user?->email,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toShowArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            ...$this->businessDetails(),
            'status' => $this->status->value,
            'rejectionReason' => $this->rejection_reason,
            'approvedAt' => $this->approved_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            ...$this->businessDetails(),
            'status' => $this->status->value,
        ];
    }

    /**
     * @return array{legalName: ?string, ein: ?string, licenseNumber: ?string, licenseState: ?string}
     */
    private function businessDetails(): array
    {
        return [
            'legalName' => $this->legal_name,
            'ein' => $this->ein,
            'licenseNumber' => $this->license_number,
            'licenseState' => $this->license_state?->value,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AgencyStatus::class,
            'approved_at' => 'datetime',
            'ein' => 'encrypted',
            'license_state' => UsState::class,
        ];
    }
}
